<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<article class="note-detail">
    <div style="display:flex; justify-content:space-between; align-items:start; gap:16px">
        <div>
            <h1 style="margin-top:0"><?= esc($note['title']) ?></h1>
            <p class="note-meta" style="color:#6b7280">
                Sahibi: <a href="<?= site_url('users/' . $note['user_id']) ?>"><?= esc($note['owner_name'] ?? 'Bilinmiyor') ?></a> ·
                <?= (int) $note['is_public'] === 1 ? 'Public' : 'Özel' ?>
                · <?= esc($note['category'] ?? 'Genel') ?>
            </p>
        </div>
        <?php if ($isOwner): ?>
            <a class="button" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>">Düzenle</a>
        <?php endif; ?>
    </div>

    <div class="note-detail-content" style="margin:24px 0; line-height:1.7; white-space:pre-wrap"><?= render_mentions($note['content']) ?></div>
    <div style="display:flex; flex-wrap:wrap; gap:8px">
        <a class="button secondary" href="<?= site_url('notes') ?>">Notlara Dön</a>
        <?php if ($canDelete): ?>
            <form method="post" action="<?= site_url('notes/' . $note['id'] . '/delete') ?>" onsubmit="return confirm('Bu not kalıcı olarak silinsin mi?')">
                <?= csrf_field() ?>
                <button class="button danger" type="submit">Sil</button>
            </form>
        <?php endif; ?>
    </div>
</article>

<section id="comments" class="comments-section">
    <div class="comments-heading">
        <h2>Yorumlar <span><?= (int) $commentsTotal ?></span></h2>
    </div>

    <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div class="alert error"><?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form class="comment-form" method="post" action="<?= site_url('notes/' . $note['id'] . '/comments') ?>">
        <?= csrf_field() ?>
        <label for="comment-content">Yorumunuz</label>
        <textarea id="comment-content" name="content" maxlength="2000" rows="4" data-speech-input required><?= esc(old('content')) ?></textarea>
        <div class="comment-form-footer"><small>En fazla 2000 karakter</small><button class="button" type="submit">Yorum Yap</button></div>
    </form>

    <?php if (empty($comments)): ?>
        <p class="comments-empty">Bu nota henüz yorum yapılmamış.</p>
    <?php else: ?>
        <div class="comment-list">
            <?php foreach ($comments as $comment): ?>
                <?php $canDeleteComment = (int) $comment['user_id'] === $userId || $isOwner || $isAdmin; ?>
                <article class="comment-card">
                    <div class="comment-meta">
                        <div><a href="<?= site_url('users/' . $comment['user_id']) ?>"><strong><?= esc($comment['username'] ?? 'Silinmiş kullanıcı') ?></strong></a><?php if ((int) $comment['user_id'] === (int) $note['user_id']): ?><span class="note-owner-badge">Not sahibi</span><?php endif; ?></div>
                        <time datetime="<?= esc($comment['created_at'], 'attr') ?>"><?= date('d.m.Y · H:i', strtotime($comment['created_at'])) ?></time>
                    </div>
                    <p><?= esc($comment['content']) ?></p>
                    <?php if ($canDeleteComment): ?>
                        <form method="post" action="<?= site_url('notes/' . $note['id'] . '/comments/' . $comment['id'] . '/delete') ?>" onsubmit="return confirm('Bu yorum silinsin mi?')">
                            <?= csrf_field() ?><button class="comment-delete" type="submit">Yorumu sil</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?= $commentsPager->links('comments') ?>
    <?php endif; ?>
</section>

<style>
    .mention-link{color:#2563eb;font-weight:700;text-decoration:none}.mention-link:hover{text-decoration:underline}
    .comments-section{padding-top:26px;margin-top:30px;border-top:1px solid #e5e7eb}.comments-heading h2{margin:0 0 18px}.comments-heading span{color:#6b7280;font-size:15px}.comment-form{padding:18px;margin-bottom:22px;border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb}.comment-form label{margin-top:0}.comment-form textarea{min-height:100px}.comment-form-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:10px}.comment-form-footer small,.comments-empty{color:#6b7280}.comment-list{display:grid;gap:12px}.comment-card{padding:16px;border:1px solid #e5e7eb;border-radius:11px}.comment-card p{margin:12px 0;line-height:1.6;white-space:pre-wrap}.comment-meta{display:flex;justify-content:space-between;gap:12px;color:#6b7280;font-size:13px}.comment-meta strong{color:#111827;font-size:14px}.note-owner-badge{padding:3px 7px;margin-left:7px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:11px}.comment-delete{padding:0;border:0;background:none;color:#dc2626;cursor:pointer;font:inherit;font-size:13px}@media(max-width:600px){.comment-meta{flex-direction:column;gap:4px}}
</style>
<?= $this->endSection() ?>
