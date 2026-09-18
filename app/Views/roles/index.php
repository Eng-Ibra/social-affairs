<h4 class="mb-3"><i class="fa-solid fa-user-shield me-2"></i>Roles &amp; Permissions</h4>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($roles as $i => $r): ?>
        <li class="nav-item"><button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#role<?= $r['id'] ?>"><?= e($r['name']) ?></button></li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
<?php foreach ($roles as $i => $r): ?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="role<?= $r['id'] ?>">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><?= e($r['name']) ?> <span class="text-muted small">— <?= e($r['description']) ?></span></span>
            <?php if ($r['slug'] === 'super_admin'): ?><span class="badge bg-secondary">Always Full Access</span><?php endif; ?>
        </div>
        <?php if ($r['slug'] !== 'super_admin'): ?>
        <form method="post" action="<?= url('/roles/' . $r['id']) ?>">
        <?= csrf_field() ?>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr><th>Module</th><?php foreach (['view','view_all','create','edit','delete','approve','export','import','manage'] as $cap): ?><th class="text-center small text-capitalize"><?= str_replace('_',' ',$cap) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($grouped as $moduleKey => $perms): ?>
                    <tr>
                        <td class="fw-semibold small text-capitalize"><?= str_replace('_',' ',$moduleKey) ?></td>
                        <?php
                        $byCapability = [];
                        foreach ($perms as $p) { $byCapability[$p['capability']] = $p; }
                        ?>
                        <?php foreach (['view','view_all','create','edit','delete','approve','export','import','manage'] as $cap): ?>
                            <td class="text-center">
                                <?php if (isset($byCapability[$cap])):
                                    $pid = $byCapability[$cap]['id'];
                                    $checked = isset($assignments[$r['id']][$pid]);
                                ?>
                                    <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?> <?= $r['slug']==='super_admin' ? 'disabled' : '' ?>>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($r['slug'] !== 'super_admin'): ?>
        <div class="card-footer"><button class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Permissions</button></div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
