<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="messages-page">
    <header class="messages-head"><div><h1>Mesajlar</h1><p>Özel konuşmalarınızı buradan takip edebilirsiniz.</p></div></header>
    <?php if ($conversations === []): ?><div class="messages-empty"><strong>Henüz bir konuşmanız yok.</strong><p>Bir kullanıcının profiline gidip “Mesaj Gönder” düğmesini kullanın.</p></div><?php else: ?>
        <div class="conversation-list">
            <?php foreach ($conversations as $conversation): ?>
                <a class="conversation-card <?= (int)$conversation['unread_count'] > 0 ? 'unread' : '' ?>" href="<?= site_url('messages/'.$conversation['id']) ?>">
                    <span class="conversation-avatar"><?= esc(mb_strtoupper(mb_substr($conversation['other_username'],0,1))) ?></span>
                    <span class="conversation-copy"><strong><?= esc($conversation['other_username']) ?></strong><span><?= esc(mb_strimwidth($conversation['last_body'] ?? 'Henüz mesaj gönderilmedi.',0,100,'…')) ?></span><small><?= $conversation['last_message_at'] ? date('d.m.Y · H:i',strtotime($conversation['last_message_at'])) : 'Yeni konuşma' ?></small></span>
                    <?php if ((int)$conversation['unread_count'] > 0): ?><span class="conversation-unread"><?= min(99,(int)$conversation['unread_count']) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?= $pager->links() ?>
    <?php endif; ?>

    <?php if ($blockedUsers !== []): ?><details class="blocked-users"><summary>Engellenen kullanıcılar (<?= count($blockedUsers) ?>)</summary><?php foreach($blockedUsers as $blocked): ?><div><a href="<?= site_url('users/'.$blocked['blocked_id']) ?>"><?= esc($blocked['username']) ?></a><form method="post" action="<?= site_url('messages/unblock/'.$blocked['blocked_id']) ?>"><?= csrf_field() ?><button class="button secondary" type="submit">Engeli kaldır</button></form></div><?php endforeach; ?></details><?php endif; ?>
</section>
<style>.messages-page{max-width:820px;margin:auto}.messages-head h1{margin:0 0 6px}.messages-head p{margin:0 0 22px;color:#6b7280}.conversation-list{display:grid;gap:10px}.conversation-card{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:13px;padding:15px;border:1px solid #e5e7eb;border-radius:13px;color:inherit;text-decoration:none}.conversation-card:hover{border-color:#93c5fd}.conversation-card.unread{border-left:5px solid #2563eb;background:#eff6ff}.conversation-avatar{display:grid;width:46px;height:46px;place-items:center;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-size:18px;font-weight:800}.conversation-copy{display:grid;min-width:0;gap:4px}.conversation-copy>span{overflow:hidden;color:#4b5563;text-overflow:ellipsis;white-space:nowrap}.conversation-copy small{color:#6b7280}.conversation-unread{display:grid;min-width:25px;height:25px;padding:0 7px;place-items:center;border-radius:999px;background:#2563eb;color:#fff;font-size:12px;font-weight:800}.messages-empty{padding:45px 20px;border:1px dashed #cbd5e1;border-radius:13px;text-align:center}.messages-empty p{color:#6b7280}.blocked-users{padding:14px;margin-top:28px;border:1px solid #e5e7eb;border-radius:11px}.blocked-users summary{cursor:pointer;font-weight:700}.blocked-users>div{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:12px}.blocked-users form{margin:0}html[data-theme="dark"] .conversation-card,html[data-theme="dark"] .blocked-users{border-color:#475569}html[data-theme="dark"] .conversation-card.unread{background:#172554}html[data-theme="dark"] .conversation-copy>span{color:#cbd5e1}@media(max-width:560px){.conversation-card{grid-template-columns:auto minmax(0,1fr)}.conversation-unread{grid-column:2;justify-self:start}.blocked-users>div{align-items:stretch;flex-direction:column}}</style>
<?= $this->endSection() ?>
