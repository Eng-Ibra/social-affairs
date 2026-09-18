<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-calendar-check me-2"></i>Calendar</h4>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fa-solid fa-plus"></i> Add Meeting/Reminder</button>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="addItemModal">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= url('/calendar') ?>">
            <?= csrf_field() ?>
            <div class="modal-header"><h6 class="modal-title">Add Meeting / Reminder / Follow-up</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-2"><label class="form-label small">Type</label>
                    <select name="item_type" class="form-select">
                        <option value="meeting">Meeting</option>
                        <option value="reminder">Reminder</option>
                        <option value="follow_up">Follow-up</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-2"><label class="form-label small">Date</label><input type="date" name="item_date" class="form-control" required></div>
                    <div class="col-6 mb-2"><label class="form-label small">End Date (optional)</label><input type="date" name="end_date" class="form-control"></div>
                </div>
                <div class="mb-2"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm btn-primary">Save</button></div>
        </form>
    </div></div>
</div>

<?php
$extraScripts = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,timeGridWeek,timeGridDay" },
        height: "auto",
        events: "' . url('/calendar/feed') . '",
        eventClick: function (info) {
            if (info.event.url) { info.jsEvent.preventDefault(); window.location.href = info.event.url; }
        }
    });
    calendar.render();
});
</script>';
?>
