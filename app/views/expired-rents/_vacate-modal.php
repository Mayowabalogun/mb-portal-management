<div class="modal fade" id="vacateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/public/expired-rents.php">
                <input type="hidden" name="action" value="vacate">
                <input type="hidden" name="rent_id" id="vacate-rent-id" value="">

                <div class="modal-header">
                    <h5 class="modal-title">Confirm Vacate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">You are about to vacate this expired rent:</p>
                    <ul class="small text-muted mb-0">
                        <li><strong>Tenant:</strong> <span id="vacate-tenant">—</span></li>
                        <li><strong>Property:</strong> <span id="vacate-property">—</span></li>
                        <li><strong>Unit:</strong> <span id="vacate-unit">—</span></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Vacate</button>
                </div>
            </form>
        </div>
    </div>
</div>
