<?php
/** @var array $message */
$statusColors = ['new' => 'success', 'read' => 'secondary', 'replied' => 'info'];
?>
<div class="admin-card mx-auto" style="max-width:700px;">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <a href="<?= url('/admin/messages') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Messages</a>
    <span class="badge text-bg-<?= $statusColors[$message['status']] ?? 'secondary' ?>"><?= e(ucfirst($message['status'])) ?></span>
  </div>

  <h5 class="mb-1"><?= e($message['subject'] ?: '(No subject)') ?></h5>
  <div class="text-white-50 small mb-3"><?= format_date($message['created_at'], 'd M Y, h:i A') ?></div>

  <ul class="list-unstyled small mb-4">
    <li><i class="bi bi-person"></i> <?= e($message['name']) ?></li>
    <li><i class="bi bi-envelope"></i> <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></li>
    <?php if ($message['phone']): ?><li><i class="bi bi-telephone"></i> <?= e($message['phone']) ?></li><?php endif; ?>
  </ul>

  <div class="glass-card p-3 mb-4"><?= nl2br(e($message['message'])) ?></div>

  <div class="d-flex gap-2">
    <a href="mailto:<?= e($message['email']) ?>?subject=Re: <?= e($message['subject'] ?: 'Your message to PowerSurge Gym') ?>" class="btn btn-ps"><i class="bi bi-reply"></i> Reply by Email</a>
    <?php if ($message['status'] !== 'replied'): ?>
    <form method="post" action="<?= url('/admin/messages/' . $message['id'] . '/mark-replied') ?>">
      <?= Security::csrfField() ?>
      <button type="submit" class="btn btn-ps-outline"><i class="bi bi-check2"></i> Mark as Replied</button>
    </form>
    <?php endif; ?>
    <form method="post" action="<?= url('/admin/messages/' . $message['id'] . '/delete') ?>" onsubmit="return confirm('Delete this message?');">
      <?= Security::csrfField() ?>
      <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
  </div>
</div>
