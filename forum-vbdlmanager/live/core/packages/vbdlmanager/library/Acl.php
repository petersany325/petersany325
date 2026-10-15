<?php
/**
 * Download Manager ACL with explicit admin Access Grants.
 *
 * Example rule the owner requested:
 * - User is in VIP SeDiv usergroup
 * - Category "VIP" is grant_required
 * - User CANNOT download from VIP until admin grants that user/group on that category
 * - Free/open categories still use legacy usergroup matrix
 */

class vbdl_Acl
{
	/** @var vbdl_Repository */
	protected $repo;

	public function __construct(vbdl_Repository $repo)
	{
		$this->repo = $repo;
	}

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
			$ids[] = 1;
		}
		return array_values(array_unique($ids));
	}

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

	public function isVip(array $userinfo)
	{
		$vip = $this->vipGroupIds();
		if (!$vip)
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
		return ($type === 'paid' || $type === 'vip');
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
			foreach ($merged as $k => $_)
			{
				if (!empty($matrix[$ugid][$k]))
				{
					$merged[$k] = 1;
				}
			}
		}
		if (!empty($userinfo['permissions']['adminpermissions']) || (!empty($userinfo['usergroupid']) && (int)$userinfo['usergroupid'] === 6))
		{
			if (!empty($matrix[6]['admin_bypass']) || !empty($merged['admin_bypass']))
			{
				$merged = array(
					'can_view_section' => 1,
					'can_download' => 1,
					'can_upload' => 1,
					'can_manage_own' => 1,
					'admin_bypass' => 1,
				);
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
		if (!empty($p['admin_bypass']) || !empty($p['can_upload']))
		{
			return true;
		}
		return $this->repo->userHasAnyUploadGrant(
			!empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0,
			$this->userGroupIds($userinfo)
		);
	}

	public function requiresGrant(array $file, $category = null)
	{
		if (!empty($file['require_grant']))
		{
			return true;
		}
		if ($category === null && !empty($file['categoryid']))
		{
			$category = $this->repo->getCategory((int)$file['categoryid']);
		}
		if (is_array($category) && !empty($category['access_mode']) && $category['access_mode'] === 'grant_required')
		{
			return true;
		}
		$paidNeeds = (int)$this->repo->getSetting('acl_paid_needs_grant', '1') === 1;
		$vipNeeds = (int)$this->repo->getSetting('acl_vip_needs_grant', '1') === 1;
		if ($this->isPaidFile($file) && ($paidNeeds || $vipNeeds))
		{
			return true;
		}
		return false;
	}

	public function hasGrant($targetType, $targetId, array $userinfo, $action = 'can_download')
	{
		return $this->repo->hasActiveGrant(
			$targetType,
			(int)$targetId,
			!empty($userinfo['userid']) ? (int)$userinfo['userid'] : 0,
			$this->userGroupIds($userinfo),
			$action
		);
	}

	public function canUploadToCategory(array $category, array $userinfo)
	{
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']))
		{
			return true;
		}
		if (empty($category['active']))
		{
			return false;
		}
		$mode = !empty($category['access_mode']) ? $category['access_mode'] : 'free_open';
		if ($mode === 'grant_required')
		{
			return $this->hasGrant('category', (int)$category['categoryid'], $userinfo, 'can_upload');
		}
		if (!empty($global['can_upload']))
		{
			return true;
		}
		return $this->hasGrant('category', (int)$category['categoryid'], $userinfo, 'can_upload');
	}

	public function uploadableCategories(array $userinfo)
	{
		$out = array();
		foreach ($this->repo->listCategories(true) as $cat)
		{
			if ($this->canUploadToCategory($cat, $userinfo))
			{
				$out[] = $cat;
			}
		}
		return $out;
	}

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
	 * @param string $action view|download
	 */
	public function canAccessFile(array $file, array $userinfo, $action = 'download')
	{
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']))
		{
			return true;
		}
		if (empty($file['active']))
		{
			return false;
		}

		$category = !empty($file['categoryid']) ? $this->repo->getCategory((int)$file['categoryid']) : null;
		$grantAction = ($action === 'view') ? 'can_view' : 'can_download';

		if ($this->requiresGrant($file, $category))
		{
			$fileId = (int)$file['fileid'];
			$catId = !empty($file['categoryid']) ? (int)$file['categoryid'] : 0;
			$ok = $this->hasGrant('file', $fileId, $userinfo, $grantAction);
			if (!$ok && $catId > 0)
			{
				$ok = $this->hasGrant('category', $catId, $userinfo, $grantAction);
			}
			if (!$ok && $action === 'view')
			{
				// Allow seeing locked card / contact admin panel.
				return $this->canViewSection($userinfo);
			}
			return $ok;
		}

		return $this->canAccessByUsergroup($file, $userinfo, $action);
	}

	public function needsVipPurchase(array $file, array $userinfo)
	{
		$global = $this->globalPerms($userinfo);
		if (!empty($global['admin_bypass']))
		{
			return false;
		}
		if (!$this->requiresGrant($file))
		{
			return false;
		}
		if ($this->canAccessFile($file, $userinfo, 'download'))
		{
			return false;
		}
		return $this->canAccessFile($file, $userinfo, 'view') || $this->canViewSection($userinfo);
	}
}
