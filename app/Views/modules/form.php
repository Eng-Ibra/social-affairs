<?php
$errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']);
$isEdit = $mode === 'edit';
$action = $isEdit ? '/m/' . $module['key'] . '/' . $record['id'] : '/m/' . $module['key'];
$crud = new \App\Controllers\CrudController();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid <?= $module['icon'] ?> me-2"></i><?= e($title) ?></h4>
    <a href="<?= url('/m/' . $module['key']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
</div>

<div class="card">
<form method="post" action="<?= url($action) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body">
    <div class="row g-3">
    <?php foreach ($module['fields'] as $f):
        if (!empty($f['auto'])) continue;
        $name = $f['name'];
        $value = old($name, $record[$name] ?? ($f['default'] ?? ''));
        if ($value === 'today') { $value = date('Y-m-d'); }
        $colClass = in_array($f['type'], ['textarea']) ? 'col-12' : 'col-md-6';
    ?>
        <div class="<?= $colClass ?>">
            <label class="form-label"><?= e($f['label']) ?> <?php if (!empty($f['required'])): ?><span class="required-mark">*</span><?php endif; ?></label>

            <?php if (!empty($f['readonly'])): ?>
                <input type="text" class="form-control" value="<?= e((string)($record[$name] ?? 'Auto-calculated')) ?>" disabled>

            <?php elseif ($f['type'] === 'textarea'): ?>
                <textarea name="<?= $name ?>" class="form-control" rows="3"><?= e((string) $value) ?></textarea>

            <?php elseif ($f['type'] === 'select'): ?>
                <select name="<?= $name ?>" class="form-select" <?= !empty($f['required']) ? 'required' : '' ?>>
                    <option value="">-- Select --</option>
                    <?php foreach ($f['options'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (string) $value === (string) $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($f['type'] === 'relation'): ?>
                <?php $opts = $crud->relationOptions($f); ?>
                <select name="<?= $name ?>" class="form-select" <?= !empty($f['required']) ? 'required' : '' ?>>
                    <option value="">-- Select --</option>
                    <?php foreach ($opts as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= (string) $value === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['label']) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($f['type'] === 'polymorphic_location'):
                $typeFieldName = 'location_type';
                $currentType = old($typeFieldName, $record[$typeFieldName] ?? '');
                $currentLabel = $isEdit ? \App\Core\LocationResolver::resolve($currentType, $record[$name] ?? null) : null;
            ?>
                <select name="<?= $name ?>" id="field_<?= $name ?>" class="form-select location-select" data-type-field="<?= $typeFieldName ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
                    <?php if ($currentLabel): ?><option value="<?= e((string) $value) ?>" selected><?= e($currentLabel) ?></option><?php endif; ?>
                </select>
                <div class="form-text">Choose the population group type above, then pick the specific location here.</div>

            <?php elseif ($f['type'] === 'file'): ?>
                <input type="file" name="<?= $name ?>" class="form-control">
                <?php if (!empty($record[$name])): ?>
                    <div class="form-text"><a href="<?= url($record[$name]) ?>" target="_blank">View current attachment</a></div>
                <?php endif; ?>

            <?php elseif ($f['type'] === 'number'): ?>
                <input type="number" name="<?= $name ?>" class="form-control" value="<?= e((string) $value) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>

            <?php elseif ($f['type'] === 'decimal'): ?>
                <input type="number" step="any" name="<?= $name ?>" class="form-control" value="<?= e((string) $value) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>

            <?php elseif ($f['type'] === 'date'): ?>
                <input type="date" name="<?= $name ?>" class="form-control" value="<?= e((string) $value) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>

            <?php else: ?>
                <input type="text" name="<?= $name ?>" class="form-control" value="<?= e((string) $value) ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
            <?php endif; ?>

            <?php foreach ($errors[$name] ?? [] as $err): ?><div class="text-danger small"><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        <a href="<?= url('/m/' . $module['key']) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div>

<?php
$hasLocation = in_array('polymorphic_location', array_column($module['fields'], 'type'));
if ($hasLocation):
$extraScripts = '<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".location-select").forEach(function (sel) {
        var typeField = document.querySelector("[name=" + sel.dataset.typeField + "]");
        if (!typeField) return;
        function reload() {
            var type = typeField.value;
            sel.innerHTML = "<option value=\"\">-- Select --</option>";
            if (!type) return;
            fetch("' . url('/location-options') . '?type=" + encodeURIComponent(type))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    data.forEach(function (opt) {
                        var o = document.createElement("option");
                        o.value = opt.id; o.textContent = opt.label;
                        sel.appendChild(o);
                    });
                });
        }
        typeField.addEventListener("change", reload);
    });
});
</script>';
endif;
?>
