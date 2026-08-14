<section class="management-section" id="notifications">
    <div class="section-heading"><div><span class="section-kicker">İletişim merkezi</span><h2>Toplu bildirimler</h2><p>Güncelleme notlarını veya genel duyuruları çevrimiçi olmasalar bile tüm kayıtlı hesaplara yayınlayın.</p></div><strong class="recipient-pill"><?= (int) $totalUserCount ?> kayıtlı alıcı</strong></div>
    <div class="broadcast-grid">
        <form class="broadcast-form management-card" method="post" action="<?= site_url('admin/notifications') ?>" data-recipient-count="<?= (int) $totalUserCount ?>">
            <?= csrf_field() ?>
            <label>Yayın türü<select name="type" required><option value="announcement" <?= old('type') === 'announcement' ? 'selected' : '' ?>>Genel duyuru</option><option value="update" <?= old('type') === 'update' ? 'selected' : '' ?>>Güncelleme notu</option></select></label>
            <label>Başlık<input name="title" value="<?= esc(old('title')) ?>" minlength="3" maxlength="150" required placeholder="Örn. Yeni özellikler yayında"></label>
            <label>İçerik<textarea name="content" minlength="5" maxlength="10000" required placeholder="Kullanıcıların göreceği ayrıntılı metni yazın..."><?= esc(old('content')) ?></textarea><small>En fazla 10.000 karakter.</small></label>
            <label>İlgili site yolu <small>(isteğe bağlı)</small><input name="target_path" value="<?= esc(old('target_path')) ?>" maxlength="255" placeholder="Örn. games veya notes"><small>Tam adres yerine yalnızca site içindeki yolu yazın.</small></label>
            <button class="button" type="submit">Tüm kayıtlı kullanıcılara yayınla</button>
        </form>
        <div class="management-card">
            <h3>Yayın geçmişi</h3>
            <?php if ($announcements === []): ?><div class="broadcast-empty">Henüz toplu bildirim yayınlanmadı.</div><?php else: ?>
                <div class="broadcast-history"><?php foreach ($announcements as $item): ?><article class="broadcast-item"><div class="broadcast-item-head"><span class="broadcast-badge <?= $item['type'] === 'update' ? 'update' : '' ?>"><?= $item['type'] === 'update' ? 'Güncelleme' : 'Duyuru' ?></span><a href="<?= site_url('announcements/' . $item['id']) ?>">Görüntüle</a></div><h4><?= esc($item['title']) ?></h4><p><?= esc(mb_strimwidth($item['content'], 0, 130, '…')) ?></p><small><?= (int) $item['recipient_count'] ?> alıcı · <?= esc($item['author_username'] ?? 'Silinmiş admin') ?> · <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></small></article><?php endforeach; ?></div>
                <?= $announcementPager->links('announcements') ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>document.querySelector('.broadcast-form')?.addEventListener('submit',function(event){if(!confirm('Bu bildirim '+Number(this.dataset.recipientCount||0)+' kayıtlı kullanıcıya gönderilecek. Çevrimdışı kullanıcılar sonraki girişlerinde görecek. Yayınlansın mı?'))event.preventDefault()});</script>
