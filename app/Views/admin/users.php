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
    }
</style>

<header class="admin-page-head">
    <h1>Kullanıcı Yönetimi</h1>

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


<?php if ($errors = session()->getFlashdata('errors')): ?>

    <div class="alert error">

        <?php foreach ($errors as $error): ?>

            <div><?= esc($error) ?></div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php if ($success = session()->getFlashdata('success')): ?>

    <div class="alert success">
        <?= esc($success) ?>
    </div>

<?php endif; ?>


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


<?= $pager->links() ?>

<?= $this->endSection() ?>