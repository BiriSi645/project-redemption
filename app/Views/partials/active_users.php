<section class="active-users-panel" aria-label="Aktif kullanıcılar" data-active-users data-users-url="<?= site_url('system/active-users') ?>" data-current-user="<?= (int) session()->get('user_id') ?>">
    <div class="active-users-heading">
        <div><span class="active-live-dot" aria-hidden="true"></span><strong>Aktif Kullanıcılar</strong></div>
        <span data-active-count><?= count($activeUsers) ?> çevrimiçi</span>
    </div>
    <?php if (empty($activeUsers)): ?>
        <p class="active-users-empty" data-active-empty>Şu anda aktif kullanıcı görünmüyor.</p>
    <?php else: ?>
        <div class="active-users-list" data-active-list>
            <?php foreach ($activeUsers as $activeUser): ?>
                <?php $isCurrentUser = (int) $activeUser['id'] === (int) session()->get('user_id'); ?>
                <a class="active-user" href="<?= site_url('users/' . $activeUser['id']) ?>" title="Profili görüntüle">
                    <span class="active-user-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr($activeUser['username'], 0, 1))) ?><i></i></span>
                    <span><strong><?= esc($activeUser['username']) ?></strong><small><?= $isCurrentUser ? 'Siz' : ((string) $activeUser['role'] === 'admin' ? 'Admin' : 'Çevrimiçi') ?></small></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<style>
    a.active-user{color:inherit;text-decoration:none}
    .active-users-panel{padding:16px 18px;margin-bottom:22px;border:1px solid #d1fae5;border-radius:13px;background:#f0fdf4}.active-users-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}.active-users-heading>div{display:flex;align-items:center;gap:8px}.active-users-heading>span{color:#15803d;font-size:12px;font-weight:700}.active-live-dot{width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.14)}.active-users-list{display:flex;gap:12px;overflow-x:auto;padding:2px 2px 5px}.active-user{display:flex;flex:0 0 auto;align-items:center;gap:9px;min-width:135px;padding:9px 11px;border-radius:10px;background:#fff}.active-user-avatar{position:relative;display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-weight:800}.active-user-avatar i{position:absolute;right:-1px;bottom:0;width:10px;height:10px;border:2px solid #fff;border-radius:50%;background:#22c55e}.active-user>span:last-child{min-width:0}.active-user strong,.active-user small{display:block}.active-user strong{max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}.active-user small{margin-top:2px;color:#6b7280;font-size:11px}.active-users-empty{margin:0;color:#6b7280;font-size:13px}html[data-theme="dark"] .active-users-panel{border-color:#166534;background:#052e16}html[data-theme="dark"] .active-user{background:#064e3b}html[data-theme="dark"] .active-user-avatar i{border-color:#064e3b}html[data-theme="dark"] .active-users-heading>span{color:#86efac}html[data-theme="dark"] .active-user small{color:#bbf7d0}
</style>
<script src="<?= base_url('js/active-users.js') ?>"></script>
