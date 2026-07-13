

<?php $__env->startSection('title'); ?>
    <?php echo e(__('Slider')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="row">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('slider-create')): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <?php echo Form::open(['url' => route('slider.store'), 'files' => true,'class'=>'create-form','id'=>'slider-form','data-pre-submit-function'=>'customValidation']); ?>

                                <div class="row mt-1">
                                    <div class="form-group col-md-12 col-sm-12 mandatory">
                                        <?php echo e(Form::label('image', __('Image'), ['class' => 'col-md-12 col-sm-12 col-12 form-label',])); ?>

                                        <?php echo e(Form::file('image', ['class' => 'form-control', 'accept' => '.jpg,.jpeg,.png','data-parsley-required'=>'true'])); ?>

                                        <?php if(count($errors) > 0): ?>
                                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="alert alert-danger error-msg"><?php echo e($error); ?></div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php echo e(Form::label('item', __('Item'), ['class' => 'col-md-12 col-sm-12 col-form-label','for'=>"items"])); ?>

                                    <div class="col-md-12 col-sm-12">
                                        <select name="item" class="form-select form-control-sm select2" id="items" aria-label="items" data-parsley-errors-messages-disabled>
                                            <?php if(isset($items)): ?>
                                                <option value="" selected><?php echo e(__("Select Advertisement")); ?></option>
                                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($row->id); ?>"><?php echo e($row->name); ?> </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center align-items-center mt-3">
                                        <h6 class="mb-0"><?php echo e(__("OR")); ?></h6>

                                    </div>
                                    <div class="col-md-12">
                                        <div class="col-md-12 form-group">
                                            <label for="category" class="form-label"><?php echo e(__('Category')); ?></label>
                                            <select name="category_id" id="category" class="form-select form-control" data-placeholder="<?php echo e(__("Select Category")); ?>">
                                                <option value=""><?php echo e(__("Select a Category")); ?></option>
                                                <?php echo $__env->make('category.dropdowntree', ['categories' => $categories], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center align-items-center mt-3">
                                        <h6 class="mb-0"><?php echo e(__("OR")); ?></h6>

                                    </div>
                                    <div class="col-md-12 col-sm-12">
                                        <?php echo e(Form::label('third_party_link', __('Third Party Link'), ['class' => 'col-md-12 col-sm-12 col-form-label ',])); ?>

                                        <?php echo e(Form::text('link', '', [
                                            'class' => 'form-control ',
                                            'placeholder' => __('link'),
                                            'id' => 'link',
                                            'data-parsley-errors-messages-disabled'
                                        ])); ?>

                                    </div>
        
                                    <div class="col-md-12 form-group mt-3">
                                        
                                            <label for="country" class="mandatory form-label"><?php echo e(__('Country')); ?></label>
                                            <select class="form-control select2" id="country" name="country_id" >
                                                <option value=""><?php echo e(__('--Select Country--')); ?></option>
                                                <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($country->id); ?>"><?php echo e($country->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                       
                                    </div>
                                    <div class="col-md-12 form-group mt-3">
                                            <label for="state" class="mandatory form-label"><?php echo e(__('State')); ?></label>
                                            <select class="form-control select2" id="state" name="state_id" >
                                                <option value=""><?php echo e(__('--Select State--')); ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 form-group mt-3">
                                            <label for="city" class="mandatory form-label"><?php echo e(__('City')); ?></label>
                                            <select class="form-control select2" id="city" name="city_id" >
                                                <option value=""><?php echo e(__('--Select City--')); ?></option>
                                        </select>
                                    </div>
                                    <div class="invalid-form-error-message"></div>
                                    <div class="col-12 d-flex justify-content-end mt-2" style="padding: 1% 2%;">
                                        <?php echo e(Form::submit(__('Save'), ['class' => 'btn btn-primary me-1 mb-1'])); ?>

                                    </div>
                                    <?php echo Form::close(); ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <div class="<?php echo e(\Illuminate\Support\Facades\Auth::user()->can('slider-create') ? "col-md-8" : "col-md-12"); ?>">
                <div class="card">
                    <div class="card-content">
                        <div class="row mt-1">
                            <div class="card-body">
                                <div class="form-group row ">
                                    <div class="col-12">
                                        <table class="table table-borderless table-striped" aria-describedby="mydesc"
                                               id="table_list" data-toggle="table"
                                               data-url="<?php echo e(route('slider.show',1)); ?>" data-click-to-select="true"
                                               data-side-pagination="server" data-pagination="true"
                                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                               data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                               data-fixed-columns="true" data-fixed-number="1" data-fixed-right-number="1"
                                               data-trim-on-search="false" data-responsive="true" data-sort-name="id"
                                               data-sort-order="desc" data-pagination-successively-size="3"
                                               data-escape="true"
                                               data-query-params="queryParams" data-id-field="id"
                                               data-show-export="true" data-export-options='{"fileName": "slider-list","ignoreColumn": ["operate"]}' data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']"
                                               data-mobile-responsive="true">
                                            <thead class="thead-dark">
                                            <tr>
                                                <th scope="col" data-field="id" data-align="center" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                                <th scope="col" data-field="image" data-align="center" data-sortable="false" data-formatter="imageFormatter"><?php echo e(__('Image')); ?></th>
                                                <th scope="col" data-field="model_type" data-align="center" data-sortable="true" data-formatter="typeFormatter"><?php echo e(__('Type')); ?></th>
                                                <th scope="col" data-field="model.name" data-sort-name="model_name" data-align="center" data-sortable="true"><?php echo e(__('Name')); ?></th>
                                                <th scope="col" data-field="country.name" data-sort-name="country_name" data-align="center" data-sortable="true"><?php echo e(__('Country')); ?></th>
                                                <th scope="col" data-field="state.name" data-sort-name="state_name" data-align="center" data-sortable="true"><?php echo e(__('State')); ?></th>
                                                <th scope="col" data-field="city.name" data-sort-name="city_name" data-align="center" data-sortable="true"><?php echo e(__('City')); ?></th>
                                                <th scope="col" data-field="third_party_link" data-align="center" data-sortable="true"><?php echo e(__('Third Party Link')); ?></th>
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('slider-delete')): ?>
                                                    <th scope="col" data-field="operate" data-escape="false" data-align="center" data-sortable="false"><?php echo e(__('Action')); ?></th>
                                                <?php endif; ?>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/slider/index.blade.php ENDPATH**/ ?>