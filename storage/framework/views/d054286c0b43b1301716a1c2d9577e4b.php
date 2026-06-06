<?php $__env->startSection('title'); ?>
    <?php echo e(__('Advertisements')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                <a class="btn btn-primary me-2"
                    href="<?php echo e(route('advertisement.create')); ?>"><?php echo e(__('Create Advertisement')); ?></a>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <style>
        /* Deleted user row styling — overrides table-striped backgrounds */
        #table_list tbody tr.deleted-user-row,
        .table-striped tbody tr.deleted-user-row:nth-of-type(odd),
        .table-striped tbody tr.deleted-user-row:nth-of-type(even) {
            background-color: #fdf0f0 !important;
            border-left: 4px solid #dc3545 !important;
        }
        #table_list tbody tr.deleted-user-row td {
            color: #999 !important;
        }
        #table_list tbody tr.deleted-user-row img {
            filter: grayscale(100%);
            opacity: 0.5;
        }
        #table_list tbody tr.deleted-user-row .badge.bg-danger {
            filter: none;
            opacity: 1;
            color: #fff !important;
        }
        #table_list tbody tr.deleted-user-row .badge {
            opacity: 0.7;
        }
        #table_list tbody tr.deleted-user-row .btn {
            opacity: 0.8;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="row g-2 mb-3 align-items-center">
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-success w-100" id="btn-active-ads">
                                    <?php echo e(__('Active Advertisements')); ?>

                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-primary w-100" id="btn-requested-ads">
                                    <?php echo e(__('Requested Advertisements')); ?>

                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" class="btn btn-secondary active w-100" id="btn-all-ads">
                                    <?php echo e(__('All Advertisements')); ?>

                                </button>
                            </div>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
                            <div class="col-12 col-md-auto ms-md-auto" id="bulk-action-btns" style="display:none;">
                                <button type="button" class="btn btn-warning" id="btn-bulk-update-status">
                                    <i class="fas fa-toggle-on me-1"></i>
                                    <?php echo e(__('Update Status')); ?>

                                    (<span id="selected-count">0</span> <?php echo e(__('selected')); ?>)
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
                        <div class="text-muted small mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo e(__('Select multiple rows using checkboxes to bulk update their status.')); ?>

                        </div>
                        <?php endif; ?>

                        <div id="filters">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-lg-4">
                                    <label for="p_category"><?php echo e(__('Category')); ?></label>
                                    <select name="category_id" id="p_category" class="form-control bootstrap-table-filter-control-category" aria-label="category" data-placeholder="<?php echo e(__('All')); ?>">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                        <?php echo $__env->make('category.dropdowntree', ['categories' => $categories], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <label for="filter"><?php echo e(__('Status')); ?></label>
                                    <select class="form-control" id="filter" data-field="status">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                        <option value="approved"><?php echo e(__('Approved')); ?></option>
                                        <option value="review"><?php echo e(__('Under Review')); ?></option>
                                        <option value="sold out"><?php echo e(__('Sold Out')); ?></option>
                                        <option value="expired"><?php echo e(__('Expired')); ?></option>
                                        <option value="inactive"><?php echo e(__('Inactive')); ?></option>
                                        <option value="soft rejected"><?php echo e(__('Soft Rejected')); ?></option>
                                        <option value="permanent rejected"><?php echo e(__('Permanent Rejected')); ?></option>
                                        <option value="resubmitted"><?php echo e(__('Resubmitted')); ?></option>
                                        <option value="deleted_user"><?php echo e(__('Deleted User')); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <label for="filter-featured-premium"><?php echo e(__('Featured')); ?></label>
                                    <select class="form-control bootstrap-table-filter-control-featured_status"
                                        id="filter_featured_premium">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                        <option value="featured"><?php echo e(__('Featured')); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <label for="filter_country"><?php echo e(__('Country')); ?></label>
                                    <select class="form-control bootstrap-table-filter-control-country"
                                        id="filter_country_item_test">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                        <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($country->name); ?>"><?php echo e($country->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div class="col-lg-1">
                                    <label for="filter_state"><?php echo e(__('State')); ?></label>
                                    <select name="state_id" class="form-control bootstrap-table-filter-control-state"
                                        id="filter_state_item">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg-1">
                                    <label for="filter_city"><?php echo e(__('City')); ?></label>
                                    <select name="city_id" class="form-control bootstrap-table-filter-control-city"
                                        id="filter_city_item">
                                        <option value=""><?php echo e(__('All')); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">

                            <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                                data-toggle="table" data-url="<?php echo e(route('advertisement.show', 'approved')); ?>"
                                data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                                data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2"
                                data-fixed-right-number="1" data-trim-on-search="false" data-escape="true"
                                data-responsive="true" data-sort-name="id" data-sort-order="desc"
                                data-pagination-successively-size="3" data-table="items" data-status-column="deleted_at"
                                data-show-export="true"
                                data-export-options='{"fileName": "item-list","ignoreColumn": ["operate"]}'
                                data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                data-mobile-responsive="false" data-responsive="true"
                                data-card-view="false"  data-toolbar="#filters"
                                data-row-style="itemRowStyle"
                                data-query-params="itemListQueryParams">
                                <thead class="thead-dark">
                                    <tr>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
                                            <th scope="col" data-field="state" data-checkbox="true"></th>
                                        <?php endif; ?>
                                        <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                        <th scope="col" data-field="name" data-sortable="true"><?php echo e(__('Name')); ?> </th>
                                        <th scope="col" data-field="description" data-sortable="true" data-formatter="descriptionFormatter"> <?php echo e(__('Description')); ?></th>
                                        <th scope="col" data-field="user_profile" data-formatter="userProfileFormatter"> <?php echo e(__('User')); ?></th>
                                        <th scope="col" data-field="price" data-sortable="true"><?php echo e(__('Price')); ?> </th>
                                        <th scope="col" data-field="min_salary" data-sortable="true" data-visible="false"> <?php echo e(__('Min Salary')); ?></th>
                                        <th scope="col" data-field="max_salary" data-sortable="true" data-visible="false"> <?php echo e(__('Max Salary')); ?></th>
                                        <th scope="col" data-field="category.name" data-sortable="true"> <?php echo e(__('Category')); ?></th>
                                        <th scope="col" data-field="image" data-sortable="false" data-escape="false" data-formatter="itemImageFormatter"> <?php echo e(__('Image')); ?></th>
                                        <th scope="col" data-field="gallery_images" data-sortable="false" data-formatter="galleryImageFormatter" data-escape="false"> <?php echo e(__('Other Images')); ?></th>
                                        <th scope="col" data-field="latitude" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Latitude')); ?></th>
                                        <th scope="col" data-field="longitude" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Longitude')); ?></th>
                                        <th scope="col" data-field="address" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Address')); ?></th>
                                        <th scope="col" data-field="contact" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Contact')); ?></th>
                                        <th scope="col" data-field="address" data-sortable="true" data-filter-control="select" data-filter-data="" data-visible="true" data-formatter="addressFormatter"> <?php echo e(__('Address')); ?></th>
                                        <th scope="col" data-field="featured_status" data-sortable="false" data-filter-control="select" data-filter-data="" data-formatter="featuredItemStatusFormatter"> <?php echo e(__('Featured or Not')); ?></th>
                                        <th scope="col" data-field="status" data-sortable="false" data-escape="false" data-formatter="itemStatusFormatter"> <?php echo e(__('Status')); ?></th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
                                            <th scope="col" data-field="active_status" data-sortable="true" data-sort-name="deleted_at" data-visible="true" data-escape="false" data-formatter="statusSwitchFormatter"> <?php echo e(__('Active')); ?></th>
                                        <?php endif; ?>
                                        <th scope="col" data-field="rejected_reason" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Rejected Reason')); ?></th>
                                        <th scope="col" data-field="created_at" data-sortable="true" data-visible="true" data-align="center"> <?php echo e(__('Created At')); ?></th>
                                        <th scope="col" data-field="expiry_date" data-sortable="true" data-visible="true" data-align="center"> <?php echo e(__('Expiry Date')); ?></th>
                                        <th scope="col" data-field="user_id" data-sortable="false" data-visible="false" data-switchable="false"> <?php echo e(__('User ID')); ?></th>
                                        <th scope="col" data-field="category_id" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Category ID')); ?></th>
                                        <th scope="col" data-field="likes" data-sortable="true" data-visible="false" data-switchable="false"> <?php echo e(__('Likes')); ?></th>
                                        <th scope="col" data-field="clicks" data-sortable="true" data-visible="true" data-align="center" data-formatter="clicksFormatter"><?php echo e(__('Clicks')); ?></th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['advertisement-update', 'advertisement-delete'])): ?>
                                            <th scope="col" data-field="operate" data-sortable="false" data-events="itemEvents" data-escape="false"> <?php echo e(__('Action')); ?></th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h4 class="modal-title fw-bold" id="myModalLabel1"><?php echo e(__('Advertisement Details')); ?></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-4">
                            
                            <div class="col-lg-8">
                                
                                <div class="ad-preview-main-image mb-3">
                                    <img id="adPreviewMainImg" src="" alt="Advertisement" class="w-100 rounded-3" onerror="onErrorImage(event)">
                                </div>
                                
                                <div class="ad-preview-gallery position-relative mb-4" id="adPreviewGallery">
                                    <button type="button" class="ad-gallery-nav ad-gallery-prev" id="adGalleryPrev"><i class="fas fa-chevron-left"></i></button>
                                    <div class="ad-gallery-track d-flex gap-2 overflow-hidden" id="adGalleryTrack"></div>
                                    <button type="button" class="ad-gallery-nav ad-gallery-next" id="adGalleryNext"><i class="fas fa-chevron-right"></i></button>
                                </div>
                                
                                <div id="adPreviewHighlights" class="mb-4 ad-preview-hidden">
                                    <span class="badge bg-primary border px-3 py-2 mb-3 fs-6"><i class="fas fa-lightbulb me-1"></i> <?php echo e(__('Highlights')); ?></span>
                                    <div class="table-responsive">
                                        <table class="table table-borderless mb-0" id="adHighlightsTable"></table>
                                    </div>
                                </div>
                                
                                <div id="adPreviewDescription" class="mb-4 ad-preview-hidden">
                                    <h5 class="fw-bold mb-3"><?php echo e(__('Description')); ?></h5>
                                    <div id="adDescriptionText"></div>
                                    <a href="javascript:void(0)" id="adDescToggle" class="text-primary ad-preview-hidden"><?php echo e(__('Show more')); ?></a>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                
                                <div class="card shadow-sm border mb-3">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-1" id="adPreviewName"></h5>
                                        <h4 class="text-primary fw-bold mb-2" id="adPreviewPrice"></h4>
                                        <div class="d-flex justify-content-end mb-2">
                                            <small class="text-muted" id="adPreviewAdId"></small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 text-muted small mb-3" id="adPreviewMeta">
                                        </div>
                                        <div class="d-flex gap-2" id="adPreviewActions"></div>
                                    </div>
                                </div>
                                
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewStatusCard">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3"><?php echo e(__('Change Status')); ?></h6>
                                        <form id="adPreviewStatusForm">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" id="adPreviewStatusId">
                                            <select name="status" class="form-select mb-2" id="adPreviewStatusSelect">
                                                <option value="review"><?php echo e(__('Under Review')); ?></option>
                                                <option value="approved"><?php echo e(__('Approve')); ?></option>
                                                <option value="soft rejected"><?php echo e(__('Soft Rejected')); ?></option>
                                                <option value="permanent rejected"><?php echo e(__('Permanent Rejected')); ?></option>
                                            </select>
                                            <div id="adPreviewRejectReasonWrap" class="mb-2 ad-preview-hidden">
                                                <label class="form-label mandatory"><?php echo e(__('Reason')); ?></label>
                                                <textarea name="rejected_reason" id="adPreviewRejectReason" class="form-control" rows="2"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><?php echo e(__('Save')); ?></button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewLocationCard">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3"><?php echo e(__('Location')); ?></h6>
                                        <div class="d-flex align-items-start gap-2 mb-3">
                                            <i class="fas fa-map-marker-alt text-muted mt-1"></i>
                                            <span id="adPreviewAddress" class="small"></span>
                                        </div>
                                        <div id="adPreviewMapWrap" class="rounded overflow-hidden ad-preview-hidden">
                                            <div id="adPreviewLeafletMap"></div>
                                        </div>
                                        <a href="#" id="adPreviewMapLink" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mt-2 ad-preview-hidden">
                                            <?php echo e(__('Show on google map')); ?>

                                        </a>
                                    </div>
                                </div>
                                
                                <div class="card shadow-sm border mb-3 ad-preview-hidden" id="adPreviewSellerCard">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="adPreviewSellerImg" src="" class="rounded-circle" onerror="onErrorImage(event)">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 fw-bold" id="adPreviewSellerName"></h6>
                                                <small class="text-muted" id="adPreviewSellerEmail"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="editStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel1"><?php echo e(__('Status')); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="create-form" action="<?php echo e(route('advertisement.approval')); ?>" method="POST" data-success-function="updateApprovalSuccess">
                            <?php echo csrf_field(); ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="hidden" name="id" id="id">
                                    <select name="status" class="form-select" id="status" aria-label="status">
                                        <option value="review"><?php echo e(__('Under Review')); ?></option>
                                        <option value="approved"><?php echo e(__('Approve')); ?></option>
                                        <option value="soft rejected"><?php echo e(__('Soft Rejected')); ?></option>
                                        <option value="permanent rejected"><?php echo e(__('Permanent Rejected')); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div id="rejected_reason_container" class="col-md-12" style="display: none;">
                                <label for="rejected_reason" class="mandatory form-label"><?php echo e(__('Reason')); ?></label>
                                <textarea name="rejected_reason" id="rejected_reason" class="form-control" placeholder=<?php echo e(__('Reason')); ?>></textarea>
                                
                            </div>
                            <input type="submit" value="<?php echo e(__('Save')); ?>" class="btn btn-primary mt-3">
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </section>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
        <div id="bulkStatusModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkStatusModalLabel">
                            <i class="fa fa-pen-to-square me-2"></i><?php echo e(__('Update Status')); ?>

                            &mdash; <span id="bulk-modal-count-label" class="fw-normal fs-6 text-muted"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        
                        <div class="mb-3">
                            <label for="bulk_status_select" class="form-label fw-semibold"><?php echo e(__('New Status')); ?></label>
                            <select id="bulk_status_select" class="form-select">
                                <option value="approved"><?php echo e(__('Approved')); ?></option>
                                <option value="review"><?php echo e(__('Under Review')); ?></option>
                                <option value="soft rejected"><?php echo e(__('Soft Rejected')); ?></option>
                                <option value="permanent rejected"><?php echo e(__('Permanent Rejected')); ?></option>
                            </select>
                        </div>

                        
                        <div id="bulk-rejected-reason-container" class="mb-3" style="display:none;">
                            <label for="bulk_rejected_reason" class="form-label mandatory"><?php echo e(__('Rejection Reason')); ?></label>
                            <textarea id="bulk_rejected_reason" class="form-control" rows="3"
                                placeholder="<?php echo e(__('Enter reason for rejection...')); ?>"></textarea>
                            <div class="invalid-feedback"><?php echo e(__('Rejection reason is required.')); ?></div>
                        </div>

                        
                        <p class="fw-semibold mb-2"><?php echo e(__('Selected Advertisements')); ?>:</p>
                        <div class="table-responsive">
                            <table id="bulk-confirm-table" class="table table-sm table-bordered table-striped"
                                aria-describedby="bulk-confirm-table-desc">
                                <caption id="bulk-confirm-table-desc" class="visually-hidden">Selected advertisements</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo e(__('ID')); ?></th>
                                        <th><?php echo e(__('Name')); ?></th>
                                        <th><?php echo e(__('Category')); ?></th>
                                        <th><?php echo e(__('Current Status')); ?></th>
                                        <th><?php echo e(__('User')); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="bulk-confirm-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="button" class="btn btn-primary" id="btn-confirm-bulk-update">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="bulk-update-spinner" role="status"></span>
                            <i class="fa fa-check me-1" id="bulk-update-icon"></i><?php echo e(__('Confirm & Update')); ?>

                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script>
        function itemRowStyle(row, index) {
            if (row.is_user_deleted) {
                return { classes: 'deleted-user-row' };
            }
            return {};
        }

        function updateApprovalSuccess() {
            $('#editStatusModal').modal('hide');
        }

        // ── Direct delegated handlers for item action buttons ──────────────────
        // Bootstrap Table's data-events system can be unreliable when combined
        // with data-click-to-select or other plugins. Using document-level
        // delegation (same pattern as common.js) guarantees these always fire.

        // "View Custom Fields" / Advertisement Preview button
        $(document).on('click', '.editdata', function (e) {
            e.preventDefault();
            let $table = $('#table_list');
            let $row = $(this).closest('tr');
            let row = $table.bootstrapTable('getData', { useCurrentPage: true })
                         .find(function(r) { return String(r.id) === String($row.find('td').eq(1).text().trim()); });

            if (!row) {
                let rowIndex = $table.find('tbody tr').index($row);
                let allData = $table.bootstrapTable('getData', { useCurrentPage: true });
                row = allData[rowIndex];
            }

            if (!row) return;

            // --- Main Image ---
            let mainImg = row.image || '';
            $('#adPreviewMainImg').attr('src', mainImg);

            // --- Gallery Thumbnails ---
            let allImages = [];
            if (mainImg) allImages.push(mainImg);
            if (row.gallery_images && row.gallery_images.length) {
                $.each(row.gallery_images, function(i, img) {
                    if (img.image && img.image !== mainImg) allImages.push(img.image);
                });
            }
            let thumbHtml = '';
            $.each(allImages, function(i, src) {
                thumbHtml += `<img src="${src}" class="ad-gallery-thumb ${i === 0 ? 'active' : ''}" onerror="onErrorImage(event)">`;
            });
            $('#adGalleryTrack').html(thumbHtml);
            $('#adPreviewGallery').toggle(allImages.length > 1);

            // --- Item Info ---
            let escapedName = $('<span>').text(row.name || '').html();
            $('#adPreviewName').html(escapedName);

            let currencySymbol = row.currency && row.currency.symbol ? row.currency.symbol : '$';
            let priceDisplay = row.price ? currencySymbol + parseFloat(row.price).toFixed(2) : '';
            if (row.min_salary && row.max_salary) {
                priceDisplay = currencySymbol + parseFloat(row.min_salary).toFixed(2) + ' - ' + currencySymbol + parseFloat(row.max_salary).toFixed(2);
            }
            $('#adPreviewPrice').text(priceDisplay);
            $('#adPreviewAdId').text('Ad id #' + row.id);

            // Meta info
            let metaHtml = '';
            if (row.created_at) metaHtml += `<span><i class="far fa-calendar-alt me-1"></i> ${trans("Listed on")}: ${row.created_at}</span>`;
            if (row.clicks !== undefined) metaHtml += `<span><i class="far fa-eye me-1"></i> ${trans("Views")}: ${row.clicks || 0}</span>`;
            if (row.likes !== undefined) metaHtml += `<span><i class="far fa-heart me-1"></i> ${trans("Favorites")}: ${row.likes || 0}</span>`;
            $('#adPreviewMeta').html(metaHtml);

            // Action buttons
            let actionsHtml = '';
            if (!row.is_user_deleted) {
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
                    actionsHtml += `<a href="${"<?php echo e(route('advertisement.edit', ':id')); ?>".replace(':id', row.id)}" class="btn btn-primary flex-fill"><?php echo e(__('Edit')); ?></a>`;
                <?php endif; ?>
            }
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-delete')): ?>
                actionsHtml += `<button type="button" class="btn flex-fill ad-preview-delete-btn" data-id="${row.id}"><?php echo e(__('Delete')); ?></button>`;
            <?php endif; ?>
            $('#adPreviewActions').html(actionsHtml);

            // --- Change Status ---
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('advertisement-update')): ?>
            if (!row.is_user_deleted && row.status !== 'sold out' && row.status !== 'expired') {
                $('#adPreviewStatusCard').show();
                $('#adPreviewStatusId').val(row.id);
                $('#adPreviewStatusSelect').val(row.status === 'approved' ? 'approved' : row.status).trigger('change');
                $('#adPreviewRejectReason').val(row.rejected_reason || '');
                adPreviewToggleRejectReason();
            } else {
                $('#adPreviewStatusCard').hide();
            }
            <?php endif; ?>

            // --- Highlights (Custom Fields) ---
            if (row.custom_fields && row.custom_fields.length > 0) {
                let highlightHtml = '';
                $.each(row.custom_fields, function(i, field) {
                    let val = '';

                    if (field.type === 'fileinput') {
                        // Controller converts fileinput value to a plain string URL
                        let fileUrl = (typeof field.value === 'string') ? field.value : (field.value?.value || '');
                        if (fileUrl) {
                            if (fileUrl.match(/\.(jpg|jpeg|png|svg)$/i)) {
                                val = `<img src="${fileUrl}" alt="" class="rounded ad-highlight-file-img" onerror="onErrorImage(event)">`;
                            } else {
                                val = `<a href="${fileUrl}" target="_blank" class="text-primary">${trans("View File")}</a>`;
                            }
                        }
                    } else {
                        // For other types, value is an object { value: "..." } or null
                        let rawVal = (typeof field.value === 'string') ? field.value : (field.value?.value || '');
                        val = rawVal ? $('<span>').text(rawVal).html() : '';
                    }

                    // Only show fields that have a value
                    if (val) {
                        let fieldIcon = field.image ? `<span class="ad-highlight-icon-wrap me-2"><img src="${field.image}" class="ad-highlight-icon" onerror="onErrorImage(event)"></span>` : '';
                        highlightHtml += `<tr>
                            <td class="fw-semibold text-nowrap ps-0 ad-highlight-name">${fieldIcon}${$('<span>').text(field.name).html()}</td>
                            <td class="text-muted px-2">:</td>
                            <td class="text-break">${val}</td>
                        </tr>`;
                    }
                });
                if (highlightHtml) {
                    $('#adHighlightsTable').html(highlightHtml);
                    $('#adPreviewHighlights').show();
                } else {
                    $('#adPreviewHighlights').hide();
                }
            } else {
                $('#adPreviewHighlights').hide();
            }

            // --- Description ---
            if (row.description) {
                let desc = $('<span>').text(row.description).html();
                let shortDesc = desc.length > 300 ? desc.substring(0, 300) + '...' : desc;
                $('#adDescriptionText').html(`<p class="text-muted mb-0" id="adDescContent">${shortDesc}</p>`);
                $('#adDescriptionText').data('full', desc).data('short', shortDesc).data('expanded', false);
                $('#adDescToggle').toggle(desc.length > 300);
                $('#adPreviewDescription').show();
            } else {
                $('#adPreviewDescription').hide();
            }

            // --- Location ---
            if (row.address) {
                let fullAddress = [row.address, row.city, row.state, row.country].filter(Boolean).join(', ');
                $('#adPreviewAddress').text(fullAddress);
                if (row.latitude && row.longitude) {
                    $('#adPreviewMapWrap').show();
                    $('#adPreviewMapLink').attr('href', `https://www.google.com/maps?q=${row.latitude},${row.longitude}`).show();
                    // Leaflet map will be initialized after modal is shown (needs visible container)
                    window._adPreviewMapCoords = { lat: parseFloat(row.latitude), lng: parseFloat(row.longitude) };
                } else {
                    $('#adPreviewMapWrap').hide();
                    $('#adPreviewMapLink').hide();
                    window._adPreviewMapCoords = null;
                }
                $('#adPreviewLocationCard').show();
            } else {
                $('#adPreviewLocationCard').hide();
                window._adPreviewMapCoords = null;
            }

            // --- Seller ---
            if (row.user) {
                $('#adPreviewSellerImg').attr('src', row.user.profile || '');
                let sellerNameHtml = $('<span>').text(row.user.name || '').html();
                if (row.is_user_deleted) {
                    sellerNameHtml += ' <span class="badge bg-danger" style="font-size: 0.7em;"><?php echo e(__("Deleted")); ?></span>';
                    $('#adPreviewSellerImg').css({'filter': 'grayscale(100%)', 'opacity': '0.6'});
                } else {
                    $('#adPreviewSellerImg').css({'filter': '', 'opacity': ''});
                }
                $('#adPreviewSellerName').html(sellerNameHtml);
                $('#adPreviewSellerEmail').text(row.user.email || '');
                $('#adPreviewSellerCard').show();
            } else {
                $('#adPreviewSellerCard').hide();
            }

            $('#editModal').modal('show');
        });

        // Initialize Leaflet map after modal is fully shown
        let adPreviewMap = null;
        let adPreviewMarker = null;
        $('#editModal').on('shown.bs.modal', function () {
            if (window._adPreviewMapCoords) {
                let coords = window._adPreviewMapCoords;
                // Destroy previous map instance if exists
                if (adPreviewMap) {
                    adPreviewMap.remove();
                    adPreviewMap = null;
                    adPreviewMarker = null;
                }
                adPreviewMap = L.map('adPreviewLeafletMap').setView([coords.lat, coords.lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(adPreviewMap);
                adPreviewMarker = L.marker([coords.lat, coords.lng]).addTo(adPreviewMap);
                setTimeout(function() {
                    adPreviewMap.invalidateSize();
                }, 200);
            }
        });
        // Clean up map when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            if (adPreviewMap) {
                adPreviewMap.remove();
                adPreviewMap = null;
                adPreviewMarker = null;
            }
        });

        // Gallery thumbnail click
        $(document).on('click', '.ad-gallery-thumb', function() {
            $('#adPreviewMainImg').attr('src', $(this).attr('src'));
            $('.ad-gallery-thumb').removeClass('active');
            $(this).addClass('active');
        });

        // Gallery navigation - prev/next image
        $(document).on('click', '#adGalleryPrev', function() {
            let $thumbs = $('.ad-gallery-thumb');
            let $active = $thumbs.filter('.active');
            let idx = $thumbs.index($active);
            let prevIdx = idx > 0 ? idx - 1 : $thumbs.length - 1;
            $thumbs.eq(prevIdx).trigger('click');
            scrollThumbIntoView($thumbs.eq(prevIdx));
        });
        $(document).on('click', '#adGalleryNext', function() {
            let $thumbs = $('.ad-gallery-thumb');
            let $active = $thumbs.filter('.active');
            let idx = $thumbs.index($active);
            let nextIdx = idx < $thumbs.length - 1 ? idx + 1 : 0;
            $thumbs.eq(nextIdx).trigger('click');
            scrollThumbIntoView($thumbs.eq(nextIdx));
        });
        // Scroll thumbnail into visible area of the track
        function scrollThumbIntoView($thumb) {
            if (!$thumb.length) return;
            let $track = $('#adGalleryTrack');
            let trackLeft = $track.scrollLeft();
            let trackWidth = $track.outerWidth();
            let thumbLeft = $thumb[0].offsetLeft;
            let thumbWidth = $thumb.outerWidth();
            if (thumbLeft < trackLeft) {
                $track.animate({ scrollLeft: thumbLeft }, 200);
            } else if (thumbLeft + thumbWidth > trackLeft + trackWidth) {
                $track.animate({ scrollLeft: thumbLeft + thumbWidth - trackWidth }, 200);
            }
        }

        // Description toggle
        $(document).on('click', '#adDescToggle', function() {
            let $wrap = $('#adDescriptionText');
            let expanded = $wrap.data('expanded');
            if (expanded) {
                $('#adDescContent').html($wrap.data('short'));
                $(this).text("<?php echo e(__('Show more')); ?>");
            } else {
                $('#adDescContent').html($wrap.data('full'));
                $(this).text("<?php echo e(__('Show less')); ?>");
            }
            $wrap.data('expanded', !expanded);
        });

        // Status reject reason toggle in preview
        function adPreviewToggleRejectReason() {
            let s = $('#adPreviewStatusSelect').val();
            $('#adPreviewRejectReasonWrap').toggle(s === 'soft rejected' || s === 'permanent rejected');
        }
        $(document).on('change', '#adPreviewStatusSelect', adPreviewToggleRejectReason);

        // Status form submit in preview
        $(document).on('submit', '#adPreviewStatusForm', function(e) {
            e.preventDefault();
            let $form = $(this);
            if ($form.data('submitting')) return false;
            $form.data('submitting', true);
            let $btn = $form.find('button[type="submit"]');
            let originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><?php echo e(__('Saving...')); ?>');
            let formData = $form.serialize();
            $.ajax({
                url: "<?php echo e(route('advertisement.approval')); ?>",
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#editModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message || "<?php echo e(__('Status updated successfully')); ?>");
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || "<?php echo e(__('Something went wrong')); ?>";
                    showErrorToast(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                    $form.data('submitting', false);
                }
            });
        });

        // Delete from preview modal
        $(document).on('click', '.ad-preview-delete-btn', function() {
            let id = $(this).data('id');
            $('#editModal').modal('hide');
            let deleteUrl = "<?php echo e(url('advertisement')); ?>/" + id;
            Swal.fire({
                title: "<?php echo e(__('Are you sure?')); ?>",
                text: "<?php echo e(__('You will not be able to revert this!')); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "<?php echo e(__('Yes, delete it!')); ?>"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: { _token: '<?php echo e(csrf_token()); ?>' },
                        success: function(response) {
                            $('#table_list').bootstrapTable('refresh');
                            showSuccessToast(response.message || "<?php echo e(__('Deleted successfully')); ?>");
                        },
                        error: function(xhr) {
                            showErrorToast(xhr.responseJSON?.message || "<?php echo e(__('Something went wrong')); ?>");
                        }
                    });
                } else {
                    // Delete cancelled — reopen the preview modal
                    $('#editModal').modal('show');
                }
            });
        });

        // "Update Status" button
        $(document).on('click', '.edit-status', function (e) {
            e.preventDefault();
            let $table = $('#table_list');
            let $row = $(this).closest('tr');
            let rowId = $(this).attr('id');

            let allData = $table.bootstrapTable('getData', { useCurrentPage: true });
            let row = allData.find(function(r) { return String(r.id) === String(rowId); });

            if (!row) {
                let rowIndex = $table.find('tbody tr').index($row);
                row = allData[rowIndex];
            }

            if (!row) return;

            $("#id").val(row.id);
            $('#status').val(row.status).trigger('change');
            $('#rejected_reason').val(row.rejected_reason);
            $('#editStatusModal').modal('show');
        });
        // ── End direct delegated handlers ──────────────────────────────────────

        // ============================================================
        // BULK SELECT / BULK APPROVAL LOGIC
        // ============================================================
        window.bulkSelectedRows = [];

        // Show/hide the Update Status button and update its count label
        function syncBulkSelectionUI() {
            const count = window.bulkSelectedRows.length;
            $('#selected-count').text(count);

            if (count > 0) {
                $('#bulk-action-btns').css('display', 'inline-block');
            } else {
                $('#bulk-action-btns').css('display', 'none');
            }
        }

        // Build confirmation table body from selected rows
        function buildBulkConfirmTable(rows) {
            let html = '';
            rows.forEach(function(row, index) {
                const statusBadgeClass = {
                    'approved': 'bg-success',
                    'review': 'bg-warning text-dark',
                    'resubmitted': 'bg-info text-dark',
                    'soft rejected': 'bg-danger',
                    'permanent rejected': 'bg-dark',
                    'sold out': 'bg-secondary',
                    'expired': 'bg-secondary',
                    'inactive': 'bg-secondary',
                }[row.status] || 'bg-secondary';

                const userName = (row.user && row.user.name) ? $('<div>').text(row.user.name).html() : '-';
                const categoryName = (row.category && row.category.name) ? $('<div>').text(row.category.name).html() : '-';
                const itemName = row.name ? $('<div>').text(row.name).html() : '-';

                html += `<tr>
                    <td>${index + 1}</td>
                    <td><strong>${row.id}</strong></td>
                    <td>${itemName}</td>
                    <td>${categoryName}</td>
                    <td><span class="badge ${statusBadgeClass}">${row.status || '-'}</span></td>
                    <td>${userName}</td>
                </tr>`;
            });
            $('#bulk-confirm-tbody').html(html);
        }

        // Open the bulk modal (single entry point)
        function openBulkModal() {
            const rows = window.bulkSelectedRows;
            if (rows.length === 0) return;

            // Update header count label
            $('#bulk-modal-count-label').text(rows.length + ' <?php echo e(__('advertisement(s) selected')); ?>');

            // Reset form
            $('#bulk_status_select').val('approved');
            $('#bulk_rejected_reason').val('').removeClass('is-invalid');
            $('#bulk-rejected-reason-container').css('display', 'none');

            // Build preview table
            buildBulkConfirmTable(rows);
            $('#bulkStatusModal').modal('show');
        }

        function updateBulkReasonVisibility() {
            const s = $('#bulk_status_select').val();
            if (s === 'soft rejected' || s === 'permanent rejected') {
                $('#bulk-rejected-reason-container').css('display', 'block');
            } else {
                $('#bulk-rejected-reason-container').css('display', 'none');
            }
        }

        // Perform the actual bulk update via AJAX
        function performBulkUpdate() {
            const rows = window.bulkSelectedRows;
            const status = $('#bulk_status_select').val();

            // Validate rejection reason if required
            const rejectedReason = $('#bulk_rejected_reason').val().trim();
            if ((status === 'soft rejected' || status === 'permanent rejected') && !rejectedReason) {
                $('#bulk_rejected_reason').addClass('is-invalid');
                return;
            }
            $('#bulk_rejected_reason').removeClass('is-invalid');

            // Show spinner
            $('#bulk-update-spinner').removeClass('d-none');
            $('#bulk-update-icon').addClass('d-none');
            $('#btn-confirm-bulk-update').prop('disabled', true);

            const ids = rows.map(r => r.id);

            $.ajax({
                url: '<?php echo e(route("advertisement.bulk-approval")); ?>',
                method: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    ids: ids,
                    status: status,
                    rejected_reason: rejectedReason
                },
                success: function(response) {
                    $('#bulkStatusModal').modal('hide');
                    window.bulkSelectedRows = [];
                    syncBulkSelectionUI();
                    $('#table_list').bootstrapTable('uncheckAll');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message || '<?php echo e(__('Status updated successfully')); ?>');
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : '<?php echo e(__('Something went wrong')); ?>';
                    showErrorToast(msg);
                },
                complete: function() {
                    $('#bulk-update-spinner').addClass('d-none');
                    $('#bulk-update-icon').removeClass('d-none');
                    $('#btn-confirm-bulk-update').prop('disabled', false);
                }
            });
        }

        // Custom queryParams function for items table to preserve filters during pagination
        function itemListQueryParams(params) {
            // Get current filter values from filter controls
            const currentFilters = {};

            // Get status filter based on button mode and dropdown selection
            const statusFilterValue = $('#filter').val();

            if (statusFilterValue === 'deleted_user') {
                // Deleted User filter — handled separately from status
                currentFilters.deleted_user = 1;
            } else if (window.itemStatusFilterMode === 'active') {
                // Active mode: always show approved (ignore dropdown)
                currentFilters.status = 'approved';
            } else if (window.itemStatusFilterMode === 'requested') {
                // Requested mode: if dropdown has a value, use it (it's already not approved)
                // Otherwise, use status_not: 'approved'
                if (statusFilterValue) {
                    currentFilters.status = statusFilterValue;
                } else {
                    currentFilters.status_not = 'approved';
                }
            } else {
                // All mode: use dropdown value if selected
                if (statusFilterValue) {
                    currentFilters.status = statusFilterValue;
                }
            }

            // FIRST: Get all non-status filters (country, state, city, featured_status)
            // These should ALWAYS be preserved regardless of button mode

            // Get featured/premium filter - try multiple selectors
            let featuredStatus = $('#filter_featured_premium').val() ||
                $('.bootstrap-table-filter-control-featured_status').val() ||
                $('select[data-field="featured_status"]').val() ||
                $('select.bootstrap-table-filter-control-featured_status').val() || '';

            // Get category filter
            let category = $('#p_category').val() ||
                $('.bootstrap-table-filter-control-category').val() ||
                $('select[data-field="category"]').val() || '';

            if (category && category.trim() !== '') {
                currentFilters.category_id = category.trim();
            }


            // Get country filter - try multiple selectors
            let country = $('#filter_country_item_test').val() ||
                $('.bootstrap-table-filter-control-country').val() ||
                $('select[data-field="country"]').val() ||
                $('select.bootstrap-table-filter-control-country').val() || '';

            // Get state filter - try multiple selectors
            let state = $('#filter_state_item').val() ||
                $('.bootstrap-table-filter-control-state').val() ||
                $('select[data-field="state"]').val() ||
                $('select.bootstrap-table-filter-control-state').val() || '';

            // Get city filter - try multiple selectors
            let city = $('#filter_city_item').val() ||
                $('.bootstrap-table-filter-control-city').val() ||
                $('select[data-field="city"]').val() ||
                $('select.bootstrap-table-filter-control-city').val() || '';

            // Add non-status filters if they have values (always preserve these)
            if (featuredStatus && featuredStatus.trim() !== '') {
                currentFilters.featured_status = featuredStatus.trim();
            }
            if (country && country.trim() !== '') {
                currentFilters.country = country.trim();
            }
            if (state && state.trim() !== '') {
                currentFilters.state = state.trim();
            }
            if (city && city.trim() !== '') {
                currentFilters.city = city.trim();
            }

            // Build query params
            const queryParams = {
                limit: params.limit,
                offset: params.offset,
                order: params.order,
                search: params.search,
                sort: params.sort
            };

            // Add filter if we have any filters
            if (Object.keys(currentFilters).length > 0) {
                queryParams.filter = JSON.stringify(currentFilters);
            }

            return queryParams;
        }

        $(document).ready(function() {

            // ---- Bulk action button handler ----
            $('#btn-bulk-update-status').on('click', function() {
                openBulkModal();
            });

            $('#btn-confirm-bulk-update').on('click', function() {
                performBulkUpdate();
            });

            // Show/hide rejection reason dynamically
            $('#bulk_status_select').on('change', function() {
                updateBulkReasonVisibility();
            });

            // Guard flag: when we programmatically call uncheckAll() it fires 'uncheck-all.bs.table'.
            // We ignore that event so it does not race with genuine user selections.
            window.resettingBulkSelection = false;

            // Bootstrap Table check/uncheck events (user-driven)
            $('#table_list').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function() {
                if (window.resettingBulkSelection) return; // ignore programmatic reset events
                // Short timeout so bootstrap-table can finish updating its internal _data state
                setTimeout(function() {
                    window.bulkSelectedRows = $('#table_list').bootstrapTable('getSelections');
                    syncBulkSelectionUI();
                }, 50);
            });

            // ---- End Bulk action button handlers ----

            // Global variable to track status filter mode
            window.itemStatusFilterMode = 'all'; // 'all', 'active', 'requested' - default to 'all'

            // Global object to track intended filter values (to prevent Bootstrap Table from resetting them)
            window.intendedFilters = {
                country: '',
                state: '',
                city: '',
                featuredStatus: '',
                status: '',
                category: ''
            };

            // Function to get current filters from filter controls and merge with status filter
            function getMergedFilters() {
                // Get current filter values from filter controls
                const currentFilters = {};

                // FIRST: Get all non-status filters (country, state, city, featured_status)
                // These should ALWAYS be preserved regardless of button mode

                // Get featured/premium filter - try multiple selectors, fallback to intended filter
                let featuredStatus = $('#filter_featured_premium').val() ||
                    $('.bootstrap-table-filter-control-featured_status').val() ||
                    $('select[data-field="featured_status"]').val() ||
                    $('select.bootstrap-table-filter-control-featured_status').val() ||
                    (window.intendedFilters ? window.intendedFilters.featuredStatus : '') || '';

                // Get category filter
                let category = $('#p_category').val() ||
                    $('.bootstrap-table-filter-control-category').val() ||
                    (window.intendedFilters ? window.intendedFilters.category : '') || '';

                if (category && category.trim() !== '') {
                    currentFilters.category_id = category.trim();
                }

                // Get country filter - try multiple selectors, fallback to intended filter
                let country = $('#filter_country_item_test').val() ||
                    $('.bootstrap-table-filter-control-country').val() ||
                    $('select[data-field="country"]').val() ||
                    $('select.bootstrap-table-filter-control-country').val() ||
                    (window.intendedFilters ? window.intendedFilters.country : '') || '';

                // Get state filter - try multiple selectors, fallback to intended filter
                let state = $('#filter_state_item').val() ||
                    $('.bootstrap-table-filter-control-state').val() ||
                    $('select[data-field="state"]').val() ||
                    $('select.bootstrap-table-filter-control-state').val() ||
                    (window.intendedFilters ? window.intendedFilters.state : '') || '';

                // Get city filter - try multiple selectors, fallback to intended filter
                let city = $('#filter_city_item').val() ||
                    $('.bootstrap-table-filter-control-city').val() ||
                    $('select[data-field="city"]').val() ||
                    $('select.bootstrap-table-filter-control-city').val() ||
                    (window.intendedFilters ? window.intendedFilters.city : '') || '';

                // Add non-status filters if they have values (always preserve these)
                if (featuredStatus && featuredStatus.trim() !== '') {
                    currentFilters.featured_status = featuredStatus.trim();
                }
                if (country && country.trim() !== '') {
                    currentFilters.country = country.trim();
                }
                if (state && state.trim() !== '') {
                    currentFilters.state = state.trim();
                }
                if (city && city.trim() !== '') {
                    currentFilters.city = city.trim();
                }

                // SECOND: Build status filter based on button mode and dropdown
                const statusFilterValue = $('#filter').val() || '';

                if (window.itemStatusFilterMode === 'active') {
                    // Active mode: always show approved (ignore dropdown)
                    currentFilters.status = 'approved';
                } else if (window.itemStatusFilterMode === 'requested') {
                    // Requested mode: if dropdown has a value, use it (it's already not approved)
                    // Otherwise, use status_not: 'approved'
                    if (statusFilterValue && statusFilterValue.trim() !== '') {
                        currentFilters.status = statusFilterValue.trim();
                    } else {
                        currentFilters.status_not = 'approved';
                    }
                } else {
                    // All mode: use dropdown value if selected
                    if (statusFilterValue && statusFilterValue.trim() !== '') {
                        currentFilters.status = statusFilterValue.trim();
                    }
                }

                return Object.keys(currentFilters).length > 0 ? currentFilters : null;
            }

            // Function to update button active states
            function updateButtonStates(activeButton) {
                $('#btn-active-ads, #btn-requested-ads, #btn-all-ads').removeClass('active');
                $(activeButton).addClass('active');
            }

            // Function to store current filter values
            function storeFilterValues() {
                // Try to get values from multiple sources (direct IDs and Bootstrap Table controls)
                // Also check global intendedFilters as fallback
                const stored = {
                    featuredStatus: $('#filter_featured_premium').val() ||
                        $('.bootstrap-table-filter-control-featured_status').val() ||
                        window.intendedFilters.featuredStatus || '',
                    country: $('#filter_country_item_test').val() ||
                        $('.bootstrap-table-filter-control-country').val() ||
                        window.intendedFilters.country || '',
                    state: $('#filter_state_item').val() ||
                        $('.bootstrap-table-filter-control-state').val() ||
                        window.intendedFilters.state || '',
                    city: $('#filter_city_item').val() ||
                        $('.bootstrap-table-filter-control-city').val() ||
                        window.intendedFilters.city || '',
                    status: $('#filter').val() || window.intendedFilters.status || ''
                };

                // Update global intended filters
                window.intendedFilters = {
                    country: stored.country,
                    state: stored.state,
                    city: stored.city,
                    featuredStatus: stored.featuredStatus,
                    status: stored.status
                };

                return stored;
            }

            // Function to restore filter values (without triggering change events to prevent loops)
            function restoreFilterValues(storedValues, skipChangeEvent) {
                if (storedValues) {
                    // Only restore if value exists and is not empty
                    if (storedValues.featuredStatus && storedValues.featuredStatus.trim() !== '') {
                        const currentVal = $('#filter_featured_premium').val();
                        if (currentVal !== storedValues.featuredStatus) {
                            $('#filter_featured_premium').val(storedValues.featuredStatus);
                        }
                        $('.bootstrap-table-filter-control-featured_status').val(storedValues.featuredStatus);
                        $('select[data-field="featured_status"]').val(storedValues.featuredStatus);
                    }
                    if (storedValues.country && storedValues.country.trim() !== '') {
                        // Restore to all possible selectors (only if value is different to prevent loops)
                        const currentCountry = $('#filter_country_item_test').val();
                        if (currentCountry !== storedValues.country) {
                            $('#filter_country_item_test').val(storedValues.country).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-country').val(storedValues.country).prop('selected',
                            true);
                        $('select[data-field="country"]').val(storedValues.country).prop('selected', true);
                        // Also try to find Bootstrap Table's generated filter control
                        $('th[data-field="country"]').find('select').val(storedValues.country).prop('selected',
                            true);
                    }
                    if (storedValues.state && storedValues.state.trim() !== '') {
                        const currentState = $('#filter_state_item').val();
                        if (currentState !== storedValues.state) {
                            $('#filter_state_item').val(storedValues.state).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-state').val(storedValues.state).prop('selected', true);
                        $('select[data-field="state"]').val(storedValues.state).prop('selected', true);
                        $('th[data-field="state"]').find('select').val(storedValues.state).prop('selected', true);
                    }
                    if (storedValues.city && storedValues.city.trim() !== '') {
                        const currentCity = $('#filter_city_item').val();
                        if (currentCity !== storedValues.city) {
                            $('#filter_city_item').val(storedValues.city).prop('selected', true);
                        }
                        $('.bootstrap-table-filter-control-city').val(storedValues.city).prop('selected', true);
                        $('select[data-field="city"]').val(storedValues.city).prop('selected', true);
                        $('th[data-field="city"]').find('select').val(storedValues.city).prop('selected', true);
                    }
                    // Status is handled separately by updateStatusDropdown
                }
            }

            // Function to update status dropdown based on button mode
            function updateStatusDropdown() {
                if (typeof window.itemStatusFilterMode === 'undefined') {
                    window.itemStatusFilterMode = 'all';
                }
                const $statusDropdown = $('#filter');
                const currentValue = $statusDropdown.val();

                // Store all options
                const allOptions = [{
                        value: '',
                        text: '<?php echo e(__('All')); ?>'
                    },
                    {
                        value: 'approved',
                        text: '<?php echo e(__('Approved')); ?>'
                    },
                    {
                        value: 'review',
                        text: '<?php echo e(__('Under Review')); ?>'
                    },
                    {
                        value: 'sold out',
                        text: '<?php echo e(__('Sold Out')); ?>'
                    },
                    {
                        value: 'expired',
                        text: '<?php echo e(__('Expired')); ?>'
                    },
                    {
                        value: 'inactive',
                        text: '<?php echo e(__('Inactive')); ?>'
                    },
                    {
                        value: 'soft rejected',
                        text: '<?php echo e(__('Soft Rejected')); ?>'
                    },
                    {
                        value: 'permanent rejected',
                        text: '<?php echo e(__('Permanent Rejected')); ?>'
                    },
                    {
                        value: 'resubmitted',
                        text: '<?php echo e(__('Resubmitted')); ?>'
                    }
                ];

                if (window.itemStatusFilterMode === 'active') {
                    // Active mode: Disable dropdown and show only approved (but it's handled by button)
                    $statusDropdown.prop('disabled', true);
                    $statusDropdown.html('<option value=""><?php echo e(__('All')); ?></option>');
                } else if (window.itemStatusFilterMode === 'requested') {
                    // Requested mode: Remove approved option, enable dropdown
                    $statusDropdown.prop('disabled', false);
                    let html = '<option value=""><?php echo e(__('All')); ?></option>';
                    allOptions.forEach(option => {
                        if (option.value !== 'approved' && option.value !== '') {
                            html += `<option value="${option.value}">${option.text}</option>`;
                        }
                    });
                    $statusDropdown.html(html);
                    // Restore previous value if it wasn't 'approved'
                    if (currentValue && currentValue !== 'approved') {
                        $statusDropdown.val(currentValue);
                    }
                } else {
                    // All mode: Show all options, enable dropdown
                    $statusDropdown.prop('disabled', false);
                    let html = '';
                    allOptions.forEach(option => {
                        html += `<option value="${option.value}">${option.text}</option>`;
                    });
                    $statusDropdown.html(html);
                    // Restore previous value
                    if (currentValue) {
                        $statusDropdown.val(currentValue);
                    }
                }
            }

            // Active Ads - only show approved, remove status filter from dropdown
            $('#btn-active-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'active';
                updateButtonStates(this);
                updateStatusDropdown();
                // Clear status dropdown filter since we're using button filter
                $('#filter').val('');

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    status: 'approved'
                });

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Requested Ads - exclude approved, allow status filter from dropdown
            $('#btn-requested-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'requested';
                updateButtonStates(this);
                updateStatusDropdown();
                // Don't clear status dropdown - user can still filter by specific status

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : JSON.stringify({
                    status_not: 'approved'
                });

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Show All - show all statuses, allow status filter from dropdown
            $('#btn-all-ads').on('click', function() {
                // Store current filter values BEFORE changing mode
                const storedFilters = storeFilterValues();

                window.itemStatusFilterMode = 'all';
                updateButtonStates(this);
                updateStatusDropdown();
                // Don't clear status dropdown - user can filter by status

                // Restore non-status filter values
                restoreFilterValues(storedFilters);

                // Get filters - read them fresh from DOM (after restoring)
                const mergedFilters = getMergedFilters();
                const filterString = mergedFilters ? JSON.stringify(mergedFilters) : '';

                $('#table_list').bootstrapTable('refresh', {
                    query: {
                        filter: filterString
                    }
                });

                // Restore filter values after refresh (Bootstrap Table might reset them)
                // Use multiple timeouts to ensure values are restored even if Bootstrap Table resets them
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 100);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 500);
                setTimeout(function() {
                    restoreFilterValues(storedFilters);
                }, 1000);
            });

            // Helper function to refresh table with current filters
            let filterChangeTimeout = null;
            let isRefreshing = false;

            function refreshTableWithFilters() {
                // Prevent multiple simultaneous refreshes
                if (isRefreshing) {
                    return;
                }

                // Clear any pending timeout
                if (filterChangeTimeout) {
                    clearTimeout(filterChangeTimeout);
                }

                // Store current filter values before refresh
                const currentFilters = storeFilterValues();

                // Debounce the refresh to prevent duplicate calls
                filterChangeTimeout = setTimeout(function() {
                    isRefreshing = true;

                    const mergedFilters = getMergedFilters();
                    const filterString = mergedFilters ? JSON.stringify(mergedFilters) : '';

                    $('#table_list').bootstrapTable('refresh', {
                        query: {
                            filter: filterString
                        }
                    });

                    // Mark as not refreshing after a delay
                    setTimeout(function() {
                        isRefreshing = false;
                    }, 1000);

                    // Restore filter values after refresh (without triggering change events)
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 100);
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 300);
                    setTimeout(function() {
                        if (currentFilters) {
                            restoreFilterValues(currentFilters, true);
                        }
                    }, 600);
                }, 150);
            }

            // When status filter dropdown changes, update based on current mode
            $('#filter').on('change', function() {
                refreshTableWithFilters();
            });

            // When country filter changes, refresh table and preserve status filter mode
            // Note: custom.js will handle loading states, we just need to refresh the table
            $(document).on('change',
                '#filter_country_item_test, .bootstrap-table-filter-control-country, select[data-field="country"], th[data-field="country"] select',
                function(e) {
                    // Get the selected country value from the element that triggered the event
                    const selectedCountry = $(this).val() || '';

                    // Skip if empty (user selected "All")
                    if (!selectedCountry) {
                        window.intendedFilters.country = '';
                        refreshTableWithFilters();
                        return;
                    }

                    // Update global intended filter IMMEDIATELY
                    window.intendedFilters.country = selectedCountry;

                    // Clear city when country changes (states will be reloaded by custom.js)
                    $('#filter_city_item').val('');
                    $('.bootstrap-table-filter-control-city').val('');
                    $('select[data-field="city"]').val('');
                    window.intendedFilters.city = '';

                    // Sync country value to ALL possible selectors IMMEDIATELY
                    const syncCountryValue = function() {
                        if (selectedCountry) {
                            $('#filter_country_item_test').val(selectedCountry).prop('selected', true);
                            $('.bootstrap-table-filter-control-country').val(selectedCountry).prop(
                                'selected', true);
                            $('select[data-field="country"]').val(selectedCountry).prop('selected', true);
                            $('th[data-field="country"]').find('select').val(selectedCountry).prop(
                                'selected', true);
                        }
                    };

                    // Sync immediately
                    syncCountryValue();

                    // Refresh table after a short delay to allow states to load
                    setTimeout(function() {
                        // Re-sync before refresh
                        syncCountryValue();
                        refreshTableWithFilters();

                        // Aggressively restore country value after refresh
                        setTimeout(syncCountryValue, 50);
                        setTimeout(syncCountryValue, 150);
                        setTimeout(syncCountryValue, 300);
                        setTimeout(syncCountryValue, 500);
                        setTimeout(syncCountryValue, 1000);
                        setTimeout(syncCountryValue, 2000);
                    }, 300);
                });

            // When state filter changes, refresh table and preserve status filter mode
            // Note: custom.js will handle loading cities, we just need to refresh the table
            $('#filter_state_item, .bootstrap-table-filter-control-state').on('change', function() {
                // Store the selected state value immediately to prevent loss
                const selectedState = $(this).val() || '';

                // Update global intended filter
                window.intendedFilters.state = selectedState;

                // Ensure state value is set in both selectors
                if (selectedState) {
                    $('#filter_state_item').val(selectedState);
                    $('.bootstrap-table-filter-control-state').val(selectedState);
                }

                // Refresh table after a short delay to allow cities to load
                setTimeout(function() {
                    // Re-ensure state value is still set before refresh
                    if (selectedState) {
                        $('#filter_state_item').val(selectedState);
                        $('.bootstrap-table-filter-control-state').val(selectedState);
                    }
                    refreshTableWithFilters();

                    // Restore state value after refresh in case Bootstrap Table reset it
                    setTimeout(function() {
                        if (selectedState) {
                            $('#filter_state_item').val(selectedState);
                            $('.bootstrap-table-filter-control-state').val(selectedState);
                        }
                    }, 100);
                }, 300);
            });

            // When city filter changes, refresh table and preserve status filter mode
            $('#filter_city_item, .bootstrap-table-filter-control-city').on('change', function() {
                // Store the selected city value immediately to prevent loss
                const selectedCity = $(this).val() || '';

                // Update global intended filter
                window.intendedFilters.city = selectedCity;

                // Ensure city value is set in both selectors
                if (selectedCity) {
                    $('#filter_city_item').val(selectedCity);
                    $('.bootstrap-table-filter-control-city').val(selectedCity);
                }

                refreshTableWithFilters();

                // Restore city value after refresh in case Bootstrap Table reset it
                setTimeout(function() {
                    if (selectedCity) {
                        $('#filter_city_item').val(selectedCity);
                        $('.bootstrap-table-filter-control-city').val(selectedCity);
                    }
                }, 100);
            });

            $('#filter_category, .bootstrap-table-filter-control-category').on('change', function() {
                const selectedCategory = $(this).val() || '';

                window.intendedFilters.category = selectedCategory;

                refreshTableWithFilters();
            });


            // When featured/premium filter changes, refresh table and preserve status filter mode
            let featuredFilterChanging = false;
            $('#filter_featured_premium, .bootstrap-table-filter-control-featured_status, select[data-field="featured_status"]')
                .on('change', function(e) {
                    // Prevent infinite loops - if we're already processing a change, skip
                    if (featuredFilterChanging) {
                        return;
                    }

                    // Get the selected featured status value
                    const selectedFeatured = $(this).val() || '';

                    // Check if value actually changed
                    const currentIntended = window.intendedFilters.featuredStatus || '';
                    if (selectedFeatured === currentIntended && selectedFeatured !== '') {
                        // Value hasn't changed, don't refresh
                        return;
                    }

                    // Set flag to prevent loops
                    featuredFilterChanging = true;

                    // Update global intended filter
                    window.intendedFilters.featuredStatus = selectedFeatured;

                    // Ensure featured status value is set in all selectors (without triggering change)
                    if (selectedFeatured) {
                        $('#filter_featured_premium').val(selectedFeatured);
                        $('.bootstrap-table-filter-control-featured_status').val(selectedFeatured);
                        $('select[data-field="featured_status"]').val(selectedFeatured);
                    }

                    // Refresh table
                    refreshTableWithFilters();

                    // Clear flag after a delay
                    setTimeout(function() {
                        featuredFilterChanging = false;
                    }, 500);
                });

            // Initialize status dropdown on page load
            updateStatusDropdown();

            // Listen to Bootstrap Table refresh events to restore filter values
            $('#table_list').on('refresh.bs.table', function() {
                // Restore all intended filter values after Bootstrap Table refreshes (without triggering change events)
                if (window.intendedFilters) {
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 50);
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 200);
                    setTimeout(function() {
                        restoreFilterValues(window.intendedFilters, true);
                    }, 500);
                }
            });

            // Clear all selections whenever the table loads new data.
            // Uses the proper bootstrap-table API (uncheckAll) so its internal _data state
            // is correctly reset — DOM-only manipulation causes getSelections() to drift.
            // The guard flag suppresses the 'uncheck-all.bs.table' event that uncheckAll() fires,
            // so it cannot interfere with a user's genuine selection.
            function clearBulkSelection() {
                window.resettingBulkSelection = true;

                // uncheckAll() resets bootstrap-table's internal _data + DOM checkboxes
                $('#table_list').bootstrapTable('uncheckAll');

                // Reset our JS tracking array and hide the bulk action buttons
                window.bulkSelectedRows = [];
                syncBulkSelectionUI();

                // Release the flag after a safe delay (longer than the 50 ms listener timeout)
                setTimeout(function() { window.resettingBulkSelection = false; }, 150);
            }

            // post-body fires after table rows are rendered (initial load, pagination, filter, refresh)
            $('#table_list').on('post-body.bs.table', function() {
                if (window.intendedFilters) {
                    restoreFilterValues(window.intendedFilters, true);
                }
                // Small delay so bootstrap-table finishes its own post-render work first
                setTimeout(function() {
                    clearBulkSelection();
                    disableNonSelectableCheckboxes();
                }, 50);
            });

            // Disable checkboxes for rows where selectable === false (sold out / expired)
            function disableNonSelectableCheckboxes() {
                const rows = $('#table_list').bootstrapTable('getData');
                $('#table_list tbody tr').each(function(index) {
                    const row = rows[index];
                    if (row && row.selectable === false) {
                        const $cb = $(this).find('input[type="checkbox"]');
                        $cb.prop('disabled', true).removeAttr('title');
                        $cb.closest('td').addClass('not-selectable-cell').css('cursor', 'not-allowed');
                    }
                });

                // Also strip any accidentally selected non-selectable rows
                window.bulkSelectedRows = window.bulkSelectedRows.filter(function(r) {
                    return r.selectable !== false;
                });
                syncBulkSelectionUI();
            }

            // Delegated tooltip on disabled cells — survives table re-render, stays inside viewport
            const notSelectableTip = '<?php echo e(__('Cannot update sold out or expired advertisements')); ?>';
            $(document).on('mouseenter', '#table_list tbody td.not-selectable-cell', function() {
                const el = this;
                let tip = bootstrap.Tooltip.getInstance(el);
                if (!tip) {
                    tip = new bootstrap.Tooltip(el, {
                        title: notSelectableTip,
                        placement: 'left',
                        container: 'body',
                        boundary: 'viewport',
                        trigger: 'manual',
                        fallbackPlacements: ['left', 'top', 'bottom']
                    });
                }
                tip.show();
            }).on('mouseleave', '#table_list tbody td.not-selectable-cell', function() {
                const tip = bootstrap.Tooltip.getInstance(this);
                if (tip) tip.hide();
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/items/index.blade.php ENDPATH**/ ?>