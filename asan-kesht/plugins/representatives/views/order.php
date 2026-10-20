<?php /** @var array $order */ /** @var array $items */ /** @var bool $can_decide */ ?>
<p><?= e((string) $order['name']) ?> — <?= e((string) $order['city']) ?> — <?= e(dealer_sale_models()[$order['sale_model']] ?? '') ?></p>
<p>وضعیت: <?= e(dealer_order_statuses()[$order['status']] ?? '') ?></p>
<ul>
  <?php foreach ($items as $item): ?>
    <li><?= e((string) $item['title']) ?> × <?= e(fa_digits((string) $item['qty'])) ?> — <?= e(money((int) $item['unit_price'])) ?>
      <?php if (isset($item['retail_price']) && (int) $item['retail_price'] > 0): ?>
        — خرده <?= e(money((int) $item['retail_price'])) ?> — سود بالقوه <?= e(money((int) ($item['potential'] ?? 0))) ?>
      <?php elseif (isset($item['retail_price'])): ?>
        — سود بالقوه ثبت نشده
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
<p>جمع <?= e(money((int) $order['total'])) ?> — نقد <?= e(money((int) $order['cash_amount'])) ?> — چک <?= e(money((int) $order['check_amount'])) ?> — نسیه <?= e(money((int) $order['credit_amount'])) ?> برای <?= e(fa_digits((string) $order['credit_days'])) ?> روز</p>
<?php if ($order['note'] !== ''): ?><p><?= e((string) $order['note']) ?></p><?php endif; ?>
<?php if ($can_decide): ?>
  <form method="post" action="<?= e(url('/admin/dealers/orders/' . $order['id'])) ?>" class="grid">
    <?= csrf_field() ?>
    <?php if ($order['status'] === 'submitted'): ?><button name="action" value="approve">تأیید و صدور سند</button><?php endif; ?>
    <?php if ($order['status'] !== 'cancelled'): ?><button name="action" value="cancel">لغو و سند اصلاحی</button><?php endif; ?>
    <?php if (!in_array($order['status'], ['submitted', 'cancelled'], true)): ?>
      <select name="status">
        <?php foreach (['preparing' => 'آماده‌سازی', 'shipped' => 'ارسال', 'delivered' => 'تحویل'] as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">ثبت مرحله</button>
    <?php endif; ?>
  </form>
<?php endif; ?>
