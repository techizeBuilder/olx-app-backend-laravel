<?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="category">
        <div class="category-header">
            <label>
                <input type="checkbox" 
                       name="selected_categories[]" 
                       value="<?php echo e($category->id); ?>" 
                       class="category-checkbox"
                       <?php echo e(in_array($category->id, $selected_categories) ? "checked" : ""); ?>>
                <?php echo e($category->name); ?>

            </label>
            <?php if(!empty($category->subcategories)): ?>
                <?php
                    // Get current language from Session
                    $currentLang = Session::get('language');
                    // Check RTL: use accessor which returns boolean (rtl != 0)
                    $isRtl = false;
                    if (!empty($currentLang)) {
                        try {
                            // Try to get raw attribute first, fallback to accessor
                            $rtlRaw = method_exists($currentLang, 'getRawOriginal') ? $currentLang->getRawOriginal('rtl') : null;
                            if ($rtlRaw !== null) {
                                $isRtl = ($rtlRaw == 1 || $rtlRaw === true);
                            } else {
                                $isRtl = ($currentLang->rtl == true || $currentLang->rtl === 1);
                            }
                        } catch (\Exception $e) {
                            $isRtl = ($currentLang->rtl == true || $currentLang->rtl === 1);
                        }
                    }
                    $arrowIcon = $isRtl ? '&#xf0d9;' : '&#xf0da;'; // fa-caret-left for RTL, fa-caret-right for LTR
                ?>
                <i style="font-size:24px"
                   class="fas toggle-button <?php echo e(in_array($category->id, $selected_all_categories) ? 'open' : ''); ?>">
                   <?php echo $arrowIcon; ?>

                </i>
            <?php endif; ?>
        </div>

        
        <div class="subcategories" 
             style="display: <?php echo e(in_array($category->id, $selected_all_categories) ? 'block' : 'none'); ?>;">
            <?php if(!empty($category->subcategories)): ?>
                <?php echo $__env->make('category.treeview', [
                    'categories' => $category->subcategories,
                    'selected_categories' => $selected_categories,
                    'selected_all_categories' => $selected_all_categories
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php /**PATH C:\wamp64\www\OLX\resources\views/category/treeview.blade.php ENDPATH**/ ?>