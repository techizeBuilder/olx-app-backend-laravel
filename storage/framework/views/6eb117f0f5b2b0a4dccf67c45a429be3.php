
<?php $__env->startSection('title'); ?>
    <?php echo e(__("Create Feature Section")); ?>

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
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('feature-section-create')): ?>
            <div class="row">
                <form action="<?php echo e(route('feature-section.store')); ?>" class="create-form" method="POST" enctype="multipart/form-data" data-parsley-validate>
                    <?php echo csrf_field(); ?>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><?php echo e(__("Add Feature Section")); ?></div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php if($key == 0): ?> active <?php endif; ?>" id="tab-<?php echo e($lang->id); ?>" data-bs-toggle="tab" data-bs-target="#lang-<?php echo e($lang->id); ?>" type="button" role="tab">
                                                <?php echo e($lang->name); ?>

                                            </button>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>

                                <div class="tab-content mt-3">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="tab-pane fade <?php if($key == 0): ?> show active <?php endif; ?>" id="lang-<?php echo e($lang->id); ?>" role="tabpanel">
                                            <input type="hidden" name="languages[]" value="<?php echo e($lang->id); ?>">

                                            <div class="form-group">
                                                <label><?php echo e(__('Title')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <input type="text" 
                                                    name="title[<?php echo e($lang->id); ?>]" 
                                                    class="form-control <?php if($lang->id == 1): ?> feature-section-name <?php endif; ?>" 
                                                    placeholder="<?php echo e(__('Title')); ?>"
                                                    value=""
                                                    <?php if($lang->id == 1): ?> data-parsley-required="true" <?php endif; ?>>
                                            </div>

                                            <?php if($lang->id == 1): ?>
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="slug" class="mandatory form-label"><?php echo e(__('Slug')); ?></label>
                                                            <input type="text" name="slug" id="slug" class="form-control feature-section-slug" data-parsley-required="true">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 form-group mandatory">
                                                        <label for="filter" class=" form-label"><?php echo e(__('Filters')); ?></label>
                                                        <select id="filter" name="filter" class="form-control select2">
                                                            <option value="most_liked"><?php echo e(__("Most Liked")); ?></option>
                                                            <option value="most_viewed"><?php echo e(__("Most Viewed")); ?></option>
                                                            <option value="price_criteria"><?php echo e(__("Price Criteria")); ?></option>
                                                            <option value="category_criteria"><?php echo e(__("Category Criteria")); ?></option>
                                                            <option value="featured_ads"><?php echo e(__("Featured Ads")); ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div id="category_criteria" class="form-group mandatory" style="display: none;">
                                                            <label for="category_id" class=" form-label"><?php echo e(__('Category')); ?></label>
                                                            <br>
                                                            <select name="category_id[]" class="select2" multiple id="category_id" data-placeholder="<?php echo e(__("Select Category")); ?>" style="width : 100%" required>
                                                                <?php echo $__env->make('category.dropdowntree', ['categories' => $categories], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div id="price_criteria" style="display:none;">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="col-md-12 form-group mandatory">
                                                                    <label for="min_price" class=" form-label"><?php echo e(__('Minimum Price')); ?></label>
                                                                    <input type="number" name="min_price" id="min_price" class="form-control" required min="1">
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="col-md-12 form-group mandatory">
                                                                    <label for="max_price" class=" form-label"><?php echo e(__('Maximum Price')); ?></label>
                                                                    <input type="number" name="max_price" id="max_price" class="form-control" required min="1" data-parsley-gt="#min_price" data-parsley-error-message="Max Price should be Greater than Min Price">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row form-group mandatory mt-3">
                                                    <label for="Field Name" class=" form-label"><?php echo e(__('Select Style for APP Section')); ?></label>
                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_1" required/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_1.png')); ?>" height="115px" width="130px" alt="style_1" class="style_image">
                                                        </label>
                                                    </div>
                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_2"/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_2.png')); ?>" height="115px" width="130px" alt="style_2" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_3"/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_3.png')); ?>" height="115px" width="130px" alt="style_3" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-2 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_4"/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_4.png')); ?>" height="115px" width="130px" alt="style_4" class="style_image">
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit" name="submit"><?php echo e(__('Submit')); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <small class="text-danger">* <?php echo e(__("To change the order, Drag the Table column Up & Down")); ?></small>
                        <table class="table table-borderless table-striped" aria-describedby="mydesc"
                               id="table_list" data-toggle="table" data-url="<?php echo e(route('feature-section.show',1)); ?>"
                               data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-search-align="right"
                               data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-fixed-columns="true" data-fixed-number="1" data-fixed-right-number="1"
                               data-trim-on-search="false" data-responsive="true"
                               data-pagination-successively-size="3" data-query-params="queryParams"
                               data-escape="true"
                               data-reorderable-rows="true"
                               data-use-row-attr-func="true" data-table="feature_sections"
                               data-show-export="true" data-export-options='{"fileName": "featured-section-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                               data-mobile-responsive="true">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                <th scope="col" data-field="style" data-formatter="styleImageFormatter"><?php echo e(__('Style')); ?></th>
                                <th scope="col" data-field="title" data-sortable="true"><?php echo e(__('Title')); ?></th>
                                <th scope="col" data-field="filter" data-sortable="true" data-formatter="filterTextFormatter"><?php echo e(__('Filters')); ?></th>
                                <th scope="col" data-field="sequence" data-sortable="true"><?php echo e(__('Sequence')); ?></th>
                                <th scope="col" data-field="min_price" data-sortable="true" data-visible="false"><?php echo e(__('Min Price')); ?></th>
                                <th scope="col" data-field="max_price" data-sortable="true" data-visible="false"><?php echo e(__('Max price')); ?></th>
                                <th scope="col" data-field="values_text" data-sortable="false" data-visible="false"><?php echo e(__('Value')); ?></th>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['feature-section-update', 'feature-section-delete'])): ?>
                                    <th scope="col" data-field="operate" data-escape="false" data-sortable="false" data-events="featuredSectionEvents"><?php echo e(__('Action')); ?></th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('feature-section-update')): ?>
        <!-- EDIT MODEL MODEL -->
            <div id="editModal" class="modal fade modal-lg" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="" class="form-horizontal edit-form" enctype="multipart/form-data" method="POST" novalidate>
                            <div class="modal-header">
                                <h5 class="modal-title" id="myModalLabel1"><?php echo e(__('Edit feature Section')); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <ul class="nav nav-tabs" id="editLangTabs" role="tablist">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?php if($key == 0): ?> active <?php endif; ?>" id="edit-tab-<?php echo e($lang->id); ?>" data-bs-toggle="tab" data-bs-target="#edit-lang-<?php echo e($lang->id); ?>" type="button" role="tab">
                                                <?php echo e($lang->name); ?>

                                            </button>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>

                                <div class="tab-content mt-3">
                                    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="tab-pane fade <?php if($key == 0): ?> show active <?php endif; ?>" id="edit-lang-<?php echo e($lang->id); ?>" role="tabpanel">
                                            <input type="hidden" name="languages[]" value="<?php echo e($lang->id); ?>">

                                            <div class="form-group">
                                                <label><?php echo e(__('Title')); ?> (<?php echo e($lang->name); ?>)</label>
                                                <input type="text" 
                                                    name="title[<?php echo e($lang->id); ?>]" 
                                                    class="form-control <?php if($lang->id == 1): ?> edit-feature-section-name <?php endif; ?>" 
                                                    placeholder="<?php echo e(__('Title')); ?>"
                                                    id="edit_title_<?php echo e($lang->id); ?>"
                                                    value=""
                                                    <?php if($lang->id == 1): ?> data-parsley-required="true" <?php endif; ?>>
                                            </div>

                                            <?php if($lang->id == 1): ?>
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="slug" class="mandatory form-label"><?php echo e(__('Slug')); ?></label>
                                                            <input type="text" name="slug" id="edit_slug" class="form-control edit-feature-section-slug" data-parsley-required="true">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 form-group mandatory">
                                                        <label for="edit_filter" class="form-label"><?php echo e(__('Filters')); ?></label>
                                                        <select id="edit_filter" name="filter" class="form-control select2">
                                                            <option value="most_liked"><?php echo e(__("Most Liked")); ?></option>
                                                            <option value="most_viewed"><?php echo e(__("Most Viewed")); ?></option>
                                                            <option value="price_criteria"><?php echo e(__("Price Criteria")); ?></option>
                                                            <option value="category_criteria"><?php echo e(__("Category Criteria")); ?></option>
                                                            <option value="featured_ads"><?php echo e(__("Featured Ads")); ?></option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="edit_price_criteria" class="row" style="display: none;">
                                                    <div class="col-md-4">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="edit_min_price" class="form-label"><?php echo e(__('Minimum Price')); ?></label>
                                                            <input type="number" name="min_price" id="edit_min_price" class="form-control" required min="1">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="col-md-12 form-group mandatory">
                                                            <label for="edit_max_price" class="form-label"><?php echo e(__('Maximum Price')); ?></label>
                                                            <input type="number" name="max_price" id="edit_max_price" class="form-control" required min="1" data-parsley-gt="#edit_min_price" data-parsley-error-message="Max Price should be Greater than Min Price">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div id="edit_category_criteria" class="col-md-12 form-group mandatory" style="display: none;">
                                                        <label for="edit_category_id" class="form-label"><?php echo e(__('Category')); ?></label>
                                                        <select name="category_id[]" class="select2" id="edit_category_id" data-placeholder="<?php echo e(__("Select Category")); ?>" multiple>
                                                            <?php echo $__env->make('category.dropdowntree', ['categories' => $categories], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row form-group mandatory mt-3">
                                                    <label for="Field Name" class=" form-label"><?php echo e(__('Select Style for APP Section')); ?></label>
                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_1" required/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_1.png')); ?>" height="115px" width="130px" alt="style_1" class="style_image">
                                                        </label>
                                                    </div>
                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_2" required/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_2.png')); ?>" height="115px" width="130px" alt="style_2" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_3" required/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_3.png')); ?>" height="115px" width="130px" alt="style_3" class="style_image">
                                                        </label>
                                                    </div>

                                                    <div class="col-md-3 col-sm-2">
                                                        <label class="radio-img">
                                                            <input type="radio" name="style" value="style_4" required/>
                                                            <img src="<?php echo e(asset('/images/app_styles/style_4.png')); ?>" height="115px" width="130px" alt="style_4" class="style_image">
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                                <button type="submit" class="btn btn-primary waves-effect waves-light"><?php echo e(__('Save')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('js'); ?>
    <script>
        
        let category_options = $('#category_id option').clone();
        $('#edit_category_id').append(category_options);
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/feature_section/index.blade.php ENDPATH**/ ?>