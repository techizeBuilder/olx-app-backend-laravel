<?php $__env->startSection('title'); ?>
    <?php echo e(__("Categories")); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0"><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                <?php if(!empty($category)): ?>
                    <a class="btn btn-primary me-2" href="<?php echo e(route('category.index')); ?>">< <?php echo e(__("Back to All Categories")); ?> </a>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-create')): ?>
                        <a class="btn btn-primary me-2" href="<?php echo e(route('category.create', ['id' => $category->id])); ?>">+ <?php echo e(__("Add Subcategory")); ?> - /<?php echo e($category->name); ?> </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-create')): ?>
                            <a class="btn btn-primary" href="<?php echo e(route('category.create')); ?>">+ <?php echo e(__("Add Category")); ?> </a>
                            <a href="<?php echo e(route('category.bulk-upload')); ?>" class="btn btn-success">
                                <i class="fas fa-upload"></i> <span class="d-none d-sm-inline"><?php echo e(__("Bulk Upload")); ?></span><span class="d-sm-none"><?php echo e(__("Upload")); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-update')): ?>
                            <a href="<?php echo e(route('category.bulk-update')); ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> <span class="d-none d-sm-inline"><?php echo e(__("Bulk Update")); ?></span><span class="d-sm-none"><?php echo e(__("Update")); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body">
                        <div class="row">
                            <div class="text-right col-md-12">
                                <a href="<?php echo e(route('category.order')); ?>">+ <?php echo e(__("Set Order of Categories")); ?> </a>
                            </div>
                        </div>
                        <table class="table table-borderless table-striped" aria-describedby="mydesc"
                               id="table_list" data-toggle="table" data-url="<?php echo e(route('category.show', $category->id ?? 0)); ?>"
                               data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200,500,2000]" data-search="true" data-search-align="right"
                               data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-trim-on-search="false" data-responsive="true" data-sort-name="sequence"
                               data-sort-order="asc" data-pagination-successively-size="3" data-query-params="queryParams"
                               data-escape="true"
                               data-table="categories" data-use-row-attr-func="true" data-mobile-responsive="false"
                               data-show-export="true" data-export-options='{"fileName": "category-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col" data-field="id" data-align="center" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                <th scope="col" data-field="name" data-sortable="true" data-formatter="categoryNameFormatter"><?php echo e(__('Name')); ?></th>
                                <th scope="col" data-field="image" data-align="center" data-formatter="imageFormatter"><?php echo e(__('Image')); ?></th>
                                <th scope="col" data-field="subcategories_count" data-align="center" data-sortable="false"><?php echo e(__('Subcategories')); ?></th>
                                <th scope="col" data-field="custom_fields_count" data-align="center" data-sortable="false"><?php echo e(__('Custom Fields')); ?></th>
                                <th scope="col" data-field="advertisements_count" data-sortable="true" data-align="center" data-formatter=""><?php echo e(__('Advertisement Count')); ?></th>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category-update')): ?>
                                    <th scope="col" data-field="status" data-width="5" data-sortable="true"  data-formatter="statusSwitchFormatter"><?php echo e(__('Active')); ?></th>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['category-update', 'category-delete'])): ?>
                                    <th scope="col" data-field="operate" data-escape="false" data-sortable="false"><?php echo e(__('Action')); ?></th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/category/index.blade.php ENDPATH**/ ?>