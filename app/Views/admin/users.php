<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .admin-page-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .admin-page-head h1 {
        margin: 0;
    }

    .admin-page-head p,.section-heading p { margin: 6px 0 0; color: #6b7280; }
    .management-tabs { display:flex; gap:8px; margin:0 0 24px; padding:6px; border:1px solid #e5e7eb; border-radius:12px; background:#f8fafc; }
    .management-tab { flex:1; padding:11px 14px; border-radius:8px; color:#475569; text-align:center; text-decoration:none; font-weight:700; }
    .management-tab:hover { background:#e2e8f0; }
    .management-tab.active { background:#2563eb; color:#fff; box-shadow:0 4px 12px rgba(37,99,235,.22); }
    .management-section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #e5e7eb; scroll-margin-top: 90px; }
    .section-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .section-heading h2 { margin:3px 0 0; }
    .section-kicker { color:#2563eb; font-size:12px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .recipient-pill { padding:7px 11px; border-radius:999px; background:#eff6ff; color:#1d4ed8; white-space:nowrap; font-size:13px; }
    .broadcast-grid { display:grid; grid-template-columns:minmax(300px,.85fr) minmax(0,1.15fr); gap:18px; }
    .management-card { padding:19px; border:1px solid #e5e7eb; border-radius:13px; background:#fff; }
    .management-card h3 { margin:0 0 14px; }
    .broadcast-form { display:grid; gap:13px; }
    .broadcast-form label { display:grid; gap:6px; font-weight:600; }
    .broadcast-form input,.broadcast-form select,.broadcast-form textarea { width:100%; box-sizing:border-box; }
    .broadcast-form textarea { min-height:150px; resize:vertical; }
    .broadcast-form small,.broadcast-item small { color:#6b7280; font-weight:400; }
    .broadcast-history { display:grid; gap:10px; }
    .broadcast-item { padding:13px; border:1px solid #e5e7eb; border-radius:10px; }
    .broadcast-item-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .broadcast-item h4 { margin:9px 0 5px; }
    .broadcast-item p { margin:0 0 9px; color:#6b7280; }
    .broadcast-badge { padding:4px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; }
    .broadcast-badge.update { background:#ecfdf5; color:#047857; }
    .broadcast-empty { padding:35px 10px; text-align:center; color:#6b7280; }

    .admin-table-wrap {
        overflow-x: auto;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        white-space: nowrap;
    }

    .admin-table form {
        display: inline-flex;
        gap: 6px;
        align-items: center;
    }

    .admin-table select {
        padding: 7px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
    }

    .admin-table .button {
        padding: 7px 10px;
    }

    .admin-table .user-state.unverified {
        background: #fef3c7;
        color: #92400e;
    }
    .user-state {
        padding: 4px 8px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: 700;
    }

    .user-state.inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 650px) {
        .admin-page-head {
            align-items: flex-start;
            flex-direction: column;
        }
        .section-heading { flex-direction:column; }
    }
    @media (max-width: 900px) { .broadcast-grid { grid-template-columns:1fr; } }
    html[data-theme="dark"] .management-section { border-color:#334155; }
    html[data-theme="dark"] .management-card,html[data-theme="dark"] .broadcast-item { background:#1e293b; border-color:#475569; color:#e5e7eb; }
    html[data-theme="dark"] .admin-page-head p,html[data-theme="dark"] .section-heading p,html[data-theme="dark"] .broadcast-item p,html[data-theme="dark"] .broadcast-form small,html[data-theme="dark"] .broadcast-item small,html[data-theme="dark"] .broadcast-empty { color:#94a3b8; }
    html[data-theme="dark"] .recipient-pill,html[data-theme="dark"] .broadcast-badge { background:#1e3a8a; color:#dbeafe; }
    html[data-theme="dark"] .broadcast-badge.update { background:#064e3b; color:#d1fae5; }
    html[data-theme="dark"] .management-tabs { background:#0f172a; border-color:#334155; }
    html[data-theme="dark"] .management-tab { color:#cbd5e1; }
    html[data-theme="dark"] .management-tab:hover { background:#334155; }
    html[data-theme="dark"] .management-tab.active { background:#2563eb; color:#fff; }
</style>

<header class="admin-page-head">
    <div><h1>Yönetim Merkezi</h1><p>Kullanıcıları yönetin, toplu bildirim yayınlayın ve sistem araçlarına erişin.</p></div>

    <div>
        <a
            class="button secondary"
            href="<?= site_url('admin') ?>"
        >
            Dashboard
        </a>

        <a
            class="button secondary"
            href="<?= site_url('admin/logs') ?>"
        >
            Loglar
        </a>
    </div>
</header>

<nav class="management-tabs" aria-label="Yönetim bölümleri">
    <?php foreach ($managementSections as $sectionKey => $sectionLabel): ?>
        <a class="management-tab <?= $activeSection === $sectionKey ? 'active' : '' ?>" href="<?= site_url('admin/users') ?>?section=<?= esc($sectionKey, 'url') ?>"><?= esc($sectionLabel) ?></a>
    <?php endforeach; ?>
</nav>


<?php if ($errors = session()->getFlashdata('errors')): ?>

    <div class="alert error">

        <?php foreach ($errors as $error): ?>

            <div><?= esc($error) ?></div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php if ($success = session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc($success) ?></div>
<?php endif; ?>

<?php if ($activeSection === 'notifications'): ?>
<?= $this->include('admin/_notifications') ?>
<?php else: ?>

<div class="section-heading"><div><span class="section-kicker">Hesap yönetimi</span><h2>Kullanıcılar</h2><p>Hesapları filtreleyin; rol ve erişim durumlarını yönetin.</p></div></div>


<form
    class="content-filter"
    method="get"
    action="<?= site_url('admin/users') ?>"
    style="
        display:grid;
        grid-template-columns:2fr 1fr 1fr auto auto;
        gap:9px;
        margin-bottom:20px;
    "
>
    <input type="hidden" name="section" value="users">

    <input
        type="search"
        name="q"
        value="<?= esc($search) ?>"
        placeholder="Kullanıcı adı veya e-posta"
    >

    <select name="role">

        <option value="">
            Tüm roller
        </option>

        <option
            value="user"
            <?= $activeRole === 'user' ? 'selected' : '' ?>
        >
            Kullanıcı
        </option>

        <option
            value="admin"
            <?= $activeRole === 'admin' ? 'selected' : '' ?>
        >
            Admin
        </option>

    </select>

    <select name="status">

        <option value="">
            Tüm durumlar
        </option>

        <option
            value="active"
            <?= $activeStatus === 'active' ? 'selected' : '' ?>
        >
            Aktif
        </option>

        <option
            value="inactive"
            <?= $activeStatus === 'inactive' ? 'selected' : '' ?>
        >
            Devre dışı
        </option>

    </select>

    <button
        class="button"
        type="submit"
    >
        Filtrele
    </button>

    <a
        class="button secondary"
        href="<?= site_url('admin/users') ?>"
    >
        Temizle
    </a>

</form>


<div class="admin-table-wrap">

    <table class="admin-table">

        <thead>

            <tr>
                <th>Kullanıcı</th>
                <th>E-posta</th>
                <th>Rol</th>
                <th>Durum</th>
                <th>Kayıt</th>
                <th>İşlemler</th>
            </tr>

        </thead>

        <tbody>

        <?php foreach ($users as $user): ?>

            <tr>

                <td>

                    <?= esc($user['username']) ?>

                    <?php if (
                        (int) $user['id']
                        ===
                        (int) session()->get('user_id')
                    ): ?>

                        (siz)

                    <?php endif; ?>

                </td>


                <td>
                    <?= esc($user['email']) ?>
                </td>


                <td>
                    <?= esc($user['role']) ?>
                </td>


                <td>

                    <?php if (empty($user['email_verified_at'])): ?>

                        <span class="user-state unverified">
                            Onaylanmamış
                        </span>

                    <?php elseif ((int) $user['is_active'] === 1): ?>

                        <span class="user-state">
                            Aktif
                        </span>

                    <?php else: ?>

                        <span class="user-state inactive">
                            Devre dışı
                        </span>

                    <?php endif; ?>

                </td>


                <td>

                    <?= $user['created_at']
                        ? date(
                            'd.m.Y',
                            strtotime($user['created_at'])
                        )
                        : '-'
                    ?>

                </td>


                <td>

                    <!-- ROL DEĞİŞTİRME -->
                    <form
                        method="post"
                        action="<?= site_url(
                            'admin/users/' . $user['id'] . '/role'
                        ) ?>"
                    >

                        <?= csrf_field() ?>

                        <select name="role">

                            <option
                                value="user"
                                <?= $user['role'] === 'user'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Kullanıcı
                            </option>

                            <option
                                value="admin"
                                <?= $user['role'] === 'admin'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Admin
                            </option>

                        </select>

                        <button
                            class="button"
                            type="submit"
                        >
                            Rolü Kaydet
                        </button>

                    </form>


                    <?php if (
                        (int) $user['id']
                        !==
                        (int) session()->get('user_id')
                    ): ?>


                        <!-- DEVRE DIŞI BIRAK / ETKİNLEŞTİR -->
                        <form
                            method="post"
                            action="<?= site_url(
                                'admin/users/' . $user['id'] . '/toggle'
                            ) ?>"
                        >

                            <?= csrf_field() ?>

                            <button
                                class="button <?= (int) $user['is_active'] === 1
                                    ? 'danger'
                                    : 'secondary'
                                ?>"
                                type="submit"
                            >

                                <?= (int) $user['is_active'] === 1
                                    ? 'Devre Dışı Bırak'
                                    : 'Etkinleştir'
                                ?>

                            </button>

                        </form>


                        <!-- KALICI OLARAK SİL -->
                        <form
                            method="post"
                            action="<?= site_url(
                                'admin/users/' . $user['id'] . '/destroy'
                            ) ?>"
                            onsubmit="
                                return confirm(
                                    'Bu kullanıcı ve kullanıcıya ait TÜM veriler kalıcı olarak silinecek. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?'
                                );
                            "
                        >

                            <?= csrf_field() ?>

                            <button
                                class="button danger"
                                type="submit"
                            >
                                Kalıcı Sil
                            </button>

                        </form>


                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>


<?= $pager->links('users') ?>

<?php endif; ?>

<?= $this->endSection() ?>
