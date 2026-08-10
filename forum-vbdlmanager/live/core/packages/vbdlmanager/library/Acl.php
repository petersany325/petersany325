<?php
/**
 * Usergroup ACL for Download Manager.
 * English Free / Paid (VIP) access control.
 */

class vbdl_Acl
{
	/** @var vbdl_Repository */
	protected $repo;

	public function __construct(vbdl_Repository $repo)
	{
		$this->repo = $repo;
	}

	/**
	 * Collect usergroup ids for current user context.
	 * @param array $userinfo vB userinfo
	 * @return int[]
	 */
	public function userGroupIds(array $userinfo)
	{
		$ids = array();
		if (!empty($userinfo['usergroupid']))
		{
			$ids[] = (int)$userinfo['usergroupid'];
		}
		if (!empty($userinfo['membergroupids']))
		{
			foreach (explode(',', $userinfo['membergroupids']) as $g)
			{
				$g = (int)trim($g);
				if ($g > 0)
				{
					$ids[] = $g;
				}
			}
		}
		if (empty($ids))
		{
			$ids[] = 1; // Guests
		}
		return array_values(array_unique($ids));
	}

	/**
	 * VIP usergroup ids configured in AdminCP (comma-separated setting).
	 * @return int[]
	 */
	public function vipGroupIds()
	{
		$raw = (string)$this->repo->getSetting('vip_usergroupids', '');
		$ids = array();
		foreach (explode(',', $raw) as $g)
		{
			$g = (int)trim($g);
			if ($g > 0)
			{
				$ids[] = $g;
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Whether the user belongs to a configured VIP usergroup.
	 */
	public function isVip(array $userinfo)
	{
		$vip = $this->vipGroupIds();
		if (empty($vip))
		{
			return false;
		}
		foreach ($this->userGroupIds($userinfo) as $ugid)
		{
			if (in_array($ugid, $vip, true))
			{
				return true;
			}
		}
		return false;
	}

	public function isPaidFile(array $file)
	{
		$type = isset($file['access_type']) ? strtolower((string)$file['access_type']) : 'free';
		return $type === 'paid';
	}

	public function globalPerms(array $userinfo)
	{
		$matrix = $this->repo->getUsergroupPerms();
		$merged = array(
			'can_view_section' => 0,
			'can_download' => 0,
			'can_upload' => 0,
			'can_manage_own' => 0,
			'admin_bypass' => 0,
		);
		foreach ($this->userGroupIds($userinfo) as $ugid)
		{
			if (empty($matrix[$ugid]))
			{
				continue;
			}
			foreach ($merged as $k => $v)
			{
				if (!empty($matrix[$ugid][$k]))
				{
					$merged[$k] = 1;
				}
			}
		}
		// Super admins (usergroup 6 commonly) get bypass if explicitly granted or if adminpermissions set
		if (!empty($userinfo['permissions']['adminpermissions']) || (!empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6))
		{
			if (!empty($matrix[6]['admin_bypass']) || !empty($merged['admin_bypass']))
			{
				$merged['admin_bypass'] = 1;
				$merged['can_view_section'] = 1;
				$merged['can_download'] = 1;
				$merged['can_upload'] = 1;
				$merged['can_manage_own'] = 1;
			}
		}
		return $merged;
	}

	public function canViewSection(array $userinfo)
	{
		$p = $this->globalPerms($userinfo);
		return !empty($p['admin_bypass']) || !empty($p['can_view_section']);
	}

	public function canUpload(array $userinfo)
	{
		$p = $this->globalPerms($userinfo);
		return !empty($p['admin_bypass']) || !empty($p['can_upload']);
	}

	/**
	 * Base usergroup ACL without Free/Paid VIP gate.
	 * @param array $file Row from vbdl_file
	 * @param array $userinfo
	 * @param string $action view|download
	 * @return bool
	 */
	protected function canAccessByUsergroup(array $file, array $userinfo, $action = 'download')
	{
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']))
		{
			return true;
		}
		if ($action === 'download' && empty($global['can_download']))
		{
			return false;
		}
		if (empty($file['active']))
		{
			return false;
		}

		$guestOk = (int)$this->repo->getSetting('guest_downloads', '0') === 1;
		$userid = !empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0;
		if ($userid < 1 && !$guestOk)
		{
			return false;
		}

		$groupIds = $this->userGroupIds($userinfo);
		$perms = array();
		if (!empty($file['inherit_category_perms']) && !empty($file['categoryid']))
		{
			$perms = $this->repo->getCategoryPerms((int)$file['categoryid']);
		}
		else
		{
			$perms = $this->repo->getFilePerms((int)$file['fileid']);
			if (empty($perms) && !empty($file['categoryid']))
			{
				$perms = $this->repo->getCategoryPerms((int)$file['categoryid']);
			}
		}

		// If no explicit perms configured, fall back to global download/view section rights
		if (empty($perms))
		{
			return $action === 'view' ? !empty($global['can_view_section']) : !empty($global['can_download']);
		}

		$field = ($action === 'view') ? 'can_view' : 'can_download';
		foreach ($groupIds as $ugid)
		{
			if (!empty($perms[$ugid][$field]) || (!empty($perms[$ugid]['can_download']) && $action === 'view'))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $file Row from vbdl_file
	 * @param array $userinfo
	 * @param string $action view|download
	 * @return bool
	 */
	public function canAccessFile(array $file, array $userinfo, $action = 'download')
	{
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']))
		{
			return true;
		}

		if (!$this->canAccessByUsergroup($file, $userinfo, $action))
		{
			return false;
		}

		// Paid downloads require VIP membership (view can still be allowed so users see the contact message)
		if ($action === 'download' && $this->isPaidFile($file))
		{
			return $this->isVip($userinfo);
		}

		return true;
	}

	/**
	 * True when user can see the file but download is locked because it is Paid/VIP.
	 */
	public function needsVipPurchase(array $file, array $userinfo)
	{
		if (!$this->isPaidFile($file))
		{
			return false;
		}
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']) || $this->isVip($userinfo))
		{
			return false;
		}
		return $this->canAccessByUsergroup($file, $userinfo, 'view');
	}
}
