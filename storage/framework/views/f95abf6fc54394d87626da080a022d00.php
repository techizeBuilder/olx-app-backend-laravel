

<?php $__env->startSection('title'); ?>
    <?php echo e(__('Create Package')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="<?php echo e(route('package.index')); ?>">
                < <?php echo e(__('Back to Packages')); ?> </a>
        </div>
        <form action="<?php echo e(route('package.store')); ?>" method="POST" class="create-form"
            data-success-function="afterPackageCreationSuccess" data-parsley-validate enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header"><?php echo e(__('Create Package')); ?></div>
                        <div class="card-body mt-2">

                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php if($key == 0): ?> active <?php endif; ?>"
                                            id="tab-<?php echo e($lang->id); ?>" data-bs-toggle="tab"
                                            data-bs-target="#lang-<?php echo e($lang->id); ?>" type="button" role="tab">
                                            <?php echo e($lang->name); ?>

                                        </button>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>

                            <div class="tab-content mt-3">
                                <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="tab-pane fade <?php if($key == 0): ?> show active <?php endif; ?>"
                                        id="lang-<?php echo e($lang->id); ?>" role="tabpanel">
                                        <input type="hidden" name="languages[]" value="<?php echo e($lang->id); ?>">
                                        <?php if($lang->id == 1): ?>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label><?php echo e(__('Package Name')); ?> (<?php echo e($lang->name); ?>) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="name[<?php echo e($lang->id); ?>]"
                                                        class="form-control" data-parsley-required="true">
                                                </div>
                                                
                                                <div class="col-md-6 form-group">
                                                    <label><?php echo e(__('IOS Product ID')); ?></label>
                                                    <input type="text" name="ios_product_id" class="form-control">
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="form-group">
                                                <label><?php echo e(__('Package Name')); ?> (<?php echo e($lang->name); ?>) <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="name[<?php echo e($lang->id); ?>]"
                                                    class="form-control">
                                            </div>
                                        <?php endif; ?>
                                        <?php if($lang->id == 1): ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label><?php echo e(__('Price')); ?> (<?php echo e($currency_symbol); ?>) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="price" class="form-control"
                                                        data-parsley-required="true" min="0" step="0.01">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label><?php echo e(__('Discount')); ?> (%) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="discount_in_percentage" class="form-control"
                                                        data-parsley-required="true" min="0" max="100"
                                                        step="0.01">
                                                </div>
                                            </div>

                                            
                                            <div class="row">
                                                <div class="col-md-12 form-group">
                                                    <label><?php echo e(__('Final Price')); ?> (<?php echo e($currency_symbol); ?>) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" name="final_price" class="form-control"
                                                        data-parsley-required="true" min="0" step="0.01">
                                                </div>
                                            </div>

                                            
                                            <div class="row">
                                                <div class="col-lg-4 form-group">
                                                    <label class="d-block"><?php echo e(__('Package Duration Type')); ?> <span class="text-danger">*</span></label>
                                                    <div class="form-check form-check-inline mt-2">
                                                        <input class="form-check-input package-duration-type" type="radio"
                                                            name="package_duration_type" id="package_duration_limited"
                                                            value="limited" checked>
                                                        <label class="form-check-label"
                                                            for="package_duration_limited"><?php echo e(__('Limited')); ?></label>
                                                    </div>
                                                    <div class="form-check form-check-inline mt-2">
                                                        <input class="form-check-input package-duration-type" type="radio"
                                                            name="package_duration_type" id="package_duration_unlimited"
                                                            value="unlimited">
                                                        <label class="form-check-label"
                                                            for="package_duration_unlimited"><?php echo e(__('Unlimited')); ?></label>
                                                    </div>
                                                </div>
                                                <div class="col-lg-8 form-group">
                                                    <div id="package_duration_input" class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label><?php echo e(__('Duration (Days)')); ?> <span class="text-danger">*</span></label>
                                                            <input type="number" name="duration" class="form-control" data-parsley-required="true" min="1" placeholder="<?php echo e(__('Enter duration in days')); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            
                                            <div class="row form-group">
                                                <label><?php echo e(__('Package Type')); ?> <span class="text-danger">*</span></label>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input package-type-radio"
                                                            type="radio" name="type" id="type_item_listing"
                                                            value="item_listing" checked>
                                                        <label class="form-check-label" for="type_item_listing">
                                                            <?php echo e(__('Ad Listing Package')); ?>

                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input package-type-radio"
                                                            type="radio" name="type"
                                                            id="type_advertisement" value="advertisement">
                                                        <label class="form-check-label" for="type_advertisement">
                                                            <?php echo e(__('Featured Ads Package')); ?>

                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            <div id="ad_listing_section" style="display: none;"
                                                class="border rounded p-3 mb-3">
                                                <h6 class="mb-3"><?php echo e(__('Ad Listing Package Settings')); ?></h6>

                                                
                                                <div class="form-group mb-3">
                                                    <label><?php echo e(__('Item Limit')); ?></label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input ads-item-limit-type" type="radio"
                                                            name="ads_item_limit_type" id="ads_limit_limited"
                                                            value="limited">
                                                        <label class="form-check-label"
                                                            for="ads_limit_limited"><?php echo e(__('Limited')); ?></label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input ads-item-limit-type" type="radio"
                                                            name="ads_item_limit_type" id="ads_limit_unlimited"
                                                            value="unlimited" checked>
                                                        <label class="form-check-label"
                                                            for="ads_limit_unlimited"><?php echo e(__('Unlimited')); ?></label>
                                                    </div>
                                                    <div id="ads_item_limit_input" style="display: none;" class="mt-2">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text myDivClass"
                                                                    style="height: 42px;">
                                                                    <span class="mySpanClass"><?php echo e(__('Number')); ?></span>
                                                                </div>
                                                            </div>
                                                            <input type="number" name="ads_item_limit"
                                                                class="form-control" min="1"
                                                                placeholder="<?php echo e(__('Enter item limit')); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                
                                                <div class="form-group mb-3">
                                                    <label><?php echo e(__('Listing Duration Type')); ?></label>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_standard" value="standard" checked>
                                                        <label class="form-check-label"
                                                            for="ads_listing_standard"><?php echo e(__('Standard (30 days)')); ?></label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_package" value="package">
                                                        <label class="form-check-label"
                                                            for="ads_listing_package"><?php echo e(__('Package')); ?></label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input ads-listing-duration-type"
                                                            type="radio" name="ads_listing_duration_type"
                                                            id="ads_listing_custom" value="custom">
                                                        <label class="form-check-label"
                                                            for="ads_listing_custom"><?php echo e(__('Custom')); ?></label>
                                                    </div>
                                                    <div id="ads_listing_duration_days_input" style="display: none;"
                                                        class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label><?php echo e(__('Days')); ?> <span class="text-danger">*</span></label>
                                                            <input type="number" name="ads_listing_duration_days" class="form-control" data-parsley-required="true" min="1" placeholder="<?php echo e(__('Enter days')); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            <div id="featured_ads_section" style="display: none;"
                                                class="border rounded p-3 mb-3">
                                                <h6 class="mb-3"><?php echo e(__('Featured Ads Package Settings')); ?></h6>

                                                
                                                <div class="form-group mb-3">
                                                    <label><?php echo e(__('Item Limit')); ?></label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input featured-item-limit-type"
                                                            type="radio" name="featured_item_limit_type"
                                                            id="featured_limit_limited" value="limited">
                                                        <label class="form-check-label"
                                                            for="featured_limit_limited"><?php echo e(__('Limited')); ?></label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input featured-item-limit-type"
                                                            type="radio" name="featured_item_limit_type"
                                                            id="featured_limit_unlimited" value="unlimited" checked>
                                                        <label class="form-check-label"
                                                            for="featured_limit_unlimited"><?php echo e(__('Unlimited')); ?></label>
                                                    </div>
                                                    <div id="featured_item_limit_input" style="display: none;"
                                                        class="mt-2">
                                                        <input type="number" name="featured_item_limit"
                                                            class="form-control" min="1"
                                                            placeholder="<?php echo e(__('Enter item limit')); ?>">
                                                    </div>
                                                </div>

                                                
                                                <div class="form-group mb-3">
                                                    <label><?php echo e(__('Featured Ads Duration Type')); ?></label>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_standard" value="standard" checked>
                                                        <label class="form-check-label"
                                                            for="featured_ads_standard"><?php echo e(__('Standard (30 days)')); ?></label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_package" value="package">
                                                        <label class="form-check-label"
                                                            for="featured_ads_package"><?php echo e(__('Package')); ?></label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input featured-ads-duration-type"
                                                            type="radio" name="featured_ads_duration_type"
                                                            id="featured_ads_custom" value="custom">
                                                        <label class="form-check-label"
                                                            for="featured_ads_custom"><?php echo e(__('Custom')); ?></label>
                                                    </div>
                                                    <div id="featured_ads_duration_days_input" style="display: none;"
                                                        class="mt-2">
                                                        <div class="col-md-12 form-group">
                                                            <label><?php echo e(__('Days')); ?> <span class="text-danger">*</span></label>
                                                            <input type="number" name="featured_ads_duration_days" class="form-control" data-parsley-required="true" min="1" placeholder="<?php echo e(__('Enter days')); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            <div class="form-group">
                                                <label><?php echo e(__('Key Points')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <div id="key_points_container_<?php echo e($lang->id); ?>">
                                                    <div class="form-group key-point-item">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="key_points[<?php echo e($lang->id); ?>][]"
                                                                class="form-control"
                                                                placeholder="<?php echo e(__('Enter key point')); ?>">
                                                            <button type="button" class="btn btn-danger remove-key-point"
                                                                style="display: none;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary mt-2 add-key-point"
                                                    data-lang-id="<?php echo e($lang->id); ?>">
                                                    <i class="fas fa-plus me-1"></i> <?php echo e(__('Add Key Point')); ?>

                                                </button>
                                            </div>

                                            
                                            <div class="form-group">
                                                <label for="icon" class="form-label"><?php echo e(__('Icon')); ?> <span
                                                        class="text-danger">*</span></label>
                                                <input type="file" name="icon" id="icon" class="form-control"
                                                    data-parsley-required="true" accept=".jpg, .jpeg, .png">
                                                <?php echo e(__('(use 256 x 256 size for better view)')); ?>

                                                <div class="img_error" style="color:#DC3545;"></div>
                                            </div>
                                        <?php else: ?>
                                            
                                            <div class="form-group">
                                                <label><?php echo e(__('Key Points')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <div id="key_points_container_<?php echo e($lang->id); ?>">
                                                    <div class="form-group key-point-item">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="key_points[<?php echo e($lang->id); ?>][]"
                                                                class="form-control"
                                                                placeholder="<?php echo e(__('Enter key point')); ?>">
                                                            <button type="button" class="btn btn-danger remove-key-point"
                                                                style="display: none;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary mt-2 add-key-point"
                                                    data-lang-id="<?php echo e($lang->id); ?>">
                                                    <i class="fas fa-plus me-1"></i> <?php echo e(__('Add Key Point')); ?>

                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header"><?php echo e(__('Category Selection')); ?></div>
                        <div class="card-body mt-2">
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_global" id="is_global"
                                    value="1">
                                <label class="form-check-label" for="is_global">
                                    <strong><?php echo e(__('Global Package (Apply to All Categories)')); ?></strong>
                                </label>
                                <small
                                    class="form-text text-muted d-block"><?php echo e(__('If checked, this package will be available for all categories.')); ?></small>
                            </div>

                            <div id="category_selection" class="sub_category_lit">
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="category">
                                        <div class="category-header">
                                            <label>
                                                <input type="checkbox" name="selected_categories[]"
                                                    value="<?php echo e($category->id); ?>" class="category-checkbox">
                                                <?php echo e($category->name); ?> 
                                            </label>
                                            <?php if(!empty($category->subcategories)): ?>
                                                <?php
                                                    $currentLang = Session::get('language');
                                                    $isRtl = false;
                                                    if (!empty($currentLang)) {
                                                        try {
                                                            $rtlRaw = method_exists($currentLang, 'getRawOriginal')
                                                                ? $currentLang->getRawOriginal('rtl')
                                                                : null;
                                                            if ($rtlRaw !== null) {
                                                                $isRtl = $rtlRaw == 1 || $rtlRaw === true;
                                                            } else {
                                                                $isRtl =
                                                                    $currentLang->rtl == true ||
                                                                    $currentLang->rtl === 1;
                                                            }
                                                        } catch (\Exception $e) {
                                                            $isRtl =
                                                                $currentLang->rtl == true || $currentLang->rtl === 1;
                                                        }
                                                    }
                                                    $arrowIcon = $isRtl ? '&#xf0d9;' : '&#xf0da;';
                                                ?>
                                                <i style='font-size:24px'
                                                    class='fas toggle-button'><?php echo $arrowIcon; ?></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="subcategories" style="display: none;">
                                            <?php if(!empty($category->subcategories)): ?>
                                                <?php echo $__env->make('category.treeview', [
                                                    'categories' => $category->subcategories,
                                                    'selected_categories' => $selected_categories,
                                                    'selected_all_categories' => $selected_all_categories,
                                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                </div>

                
                

                <div class="col-md-12 text-end">
                    <input type="submit" class="btn btn-primary" value="<?php echo e(__('Save and Back')); ?>">
                </div>

            </div>
        </form>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        $(document).ready(function() {
            // Package type radio buttons - only one can be selected
            $('.package-type-radio').on('change', function() {
                const selectedType = $(this).val();

                if (selectedType === 'item_listing') {
                    $('#ad_listing_section').show();
                    $('#featured_ads_section').hide();
                    // Enable category selection for item_listing
                    // Uncheck and enable global package checkbox
                    $('#is_global').prop('checked', false).prop('disabled', false);
                    $('.category-checkbox').prop('disabled', false);
                    $('#category_selection').show();
                } else if (selectedType === 'advertisement') {
                    $('#ad_listing_section').hide();
                    $('#featured_ads_section').show();
                    // Disable category selection for advertisement (featured ads)
                    // Set as global package for featured ads
                    $('#is_global').prop('checked', true).prop('disabled', true);
                    $('.category-checkbox').prop('checked', false).prop('disabled', true);
                    $('#category_selection').hide();
                }
            });
            
            // Initialize on page load
            const initialType = $('.package-type-radio:checked').val();
            if (initialType) {
                $('.package-type-radio[value="' + initialType + '"]').trigger('change');
            }

            // Ad listing item limit toggle
            $('.ads-item-limit-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#ads_item_limit_input').show();
                } else {
                    $('#ads_item_limit_input').hide();
                }
            });

            // Ad listing duration type toggle
            $('.ads-listing-duration-type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#ads_listing_duration_days_input').show();
                } else {
                    $('#ads_listing_duration_days_input').hide();
                }
            });

            // Featured item limit toggle
            $('.featured-item-limit-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#featured_item_limit_input').show();
                } else {
                    $('#featured_item_limit_input').hide();
                }
            });

            // Featured ads duration type toggle
            $('.featured-ads-duration-type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#featured_ads_duration_days_input').show();
                } else {
                    $('#featured_ads_duration_days_input').hide();
                }
            });

            // Package duration type toggle
            $('.package-duration-type').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#package_duration_input').show();
                    $('#package_duration_input input[name="duration"]').attr('data-parsley-required',
                        'true');
                } else {
                    $('#package_duration_input').hide();
                    $('#package_duration_input input[name="duration"]').removeAttr('data-parsley-required');
                }
            });

            // Add key point
            $(document).on('click', '.add-key-point', function() {
                const langId = $(this).data('lang-id');
                const container = $('#key_points_container_' + langId);
                const newPoint = container.find('.key-point-item').first().clone();
                newPoint.find('input').val('');
                newPoint.find('.remove-key-point').show();
                container.append(newPoint);
                updateRemoveButtons();
            });

            // Remove key point
            $(document).on('click', '.remove-key-point', function() {
                const container = $(this).closest('#key_points_container_' + $(this).closest('.tab-pane')
                    .find('input[name="languages[]"]').val());
                if ($(this).closest('.key-point-item').siblings('.key-point-item').length > 0) {
                    $(this).closest('.key-point-item').remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                $('.key-point-item').each(function() {
                    const container = $(this).closest('#key_points_container_' + $(this).closest(
                        '.tab-pane').find('input[name="languages[]"]').val());
                    const count = container.find('.key-point-item').length;
                    container.find('.remove-key-point').toggle(count > 1);
                });
            }

            // Global package toggle
            $('#is_global').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#category_selection').hide();
                    $('.category-checkbox').prop('checked', false);
                } else {
                    $('#category_selection').show();
                }
            });

            $(document).on('click', '.category-checkbox', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();

                const isChecked = $(this).is(':checked');
                const $category = $(this).closest('.category');

                // If parent: check/uncheck all children
                $category.find('.subcategories .category-checkbox').prop('checked', isChecked);

                // If child: update parent state
                updateParentCheckbox($category);
            });

            function updateParentCheckbox($childCategory) {
                const $parentCategory = $childCategory.parent().closest('.category');
                if ($parentCategory.length === 0) return;

                const $parentCheckbox = $parentCategory.find('> .category-header .category-checkbox');
                const $siblings = $parentCategory.find('> .subcategories .category > .category-header .category-checkbox');
                const allChecked = $siblings.length > 0 && $siblings.filter(':checked').length === $siblings.length;

                $parentCheckbox.prop('checked', allChecked);

                // Recurse up the tree
                updateParentCheckbox($parentCategory);
            }

            $(document).on('click', '.category-header label', function(e) {

                if ($(e.target).is('input[type="checkbox"]')) {
                    e.stopPropagation();
                }
            });
            
            // Prevent category header from triggering toggle except when clicking the toggle button
            $(document).on('click', '.category-header', function(e) {
                // If clicking on toggle button, let it handle
                if ($(e.target).hasClass('toggle-button') || $(e.target).closest('.toggle-button').length) {
                    return;
                }
                // If clicking on checkbox or label, let it handle
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label') || $(e.target).closest('label').length) {
                    return;
                }
                // Otherwise, prevent any action
                e.stopPropagation();
            });
        });

        function afterPackageCreationSuccess() {
            setTimeout(function() {
                window.location.href = "<?php echo e(route('package.index')); ?>";
            }, 1000)
        }

        // Auto-calculate final price based on price and discount
        function calculateFinalPrice() {
            const price = parseFloat($('input[name="price"]').val()) || 0;
            const discount = parseFloat($('input[name="discount_in_percentage"]').val()) || 0;
            
            if (price > 0 && discount >= 0 && discount <= 100) {
                const discountAmount = (price * discount) / 100;
                const finalPrice = price - discountAmount;
                $('input[name="final_price"]').val(finalPrice.toFixed(2));
            }
        }

        $('input[name="price"], input[name="discount_in_percentage"]').on('input', calculateFinalPrice);
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/packages/create.blade.php ENDPATH**/ ?>