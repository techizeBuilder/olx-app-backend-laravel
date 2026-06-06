<?php $__env->startSection('title'); ?>
    <?php echo e(__('Home')); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<style>
.card_title {
    white-space: normal;
    word-break: break-word;
}
</style>
    <section class="section">
        <div class="dashboard_title mb-3"><?php echo e(__("Hi, Admin")); ?></div>
        <div class="row mb-3 d-flex">
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="row">
                    <div class="col-sm-6 col-12 mb-3">
                        <a href="<?php echo e(url('customer')); ?>">
                            <div class="card h-100" style="width: 100%;">
                                <div class="total_customer d-flex">
                                    <div class="curtain"></div>
                                    <div class="row">
                                        <div class="col-4 col-md-12 ">
                                            <div class="svg_icon align-items-center d-flex justify-content-center me-3">
                                                <span class="fa fa-users text-white fa-2x"></span>
                                            </div>
                                        </div>
                                        <div class="col-8 col-md-12">
                                            <div class="total_number"><?php echo e($user_count); ?></div>
                                            <div class="card_title"><?php echo e(__('Total Customers')); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-12 mb-3">
                        <a href="<?php echo e(url('advertisement')); ?>">
                            <div class="card h-100" style="width: 100%;">
                                <div class="total_items d-flex">
                                    <div class="curtain"></div>
                                    <div class="row">
                                        <div class="col-4 col-md-12 ">
                                            <div class="svg_icon align-items-center d-flex justify-content-center me-3">
                                                <span class="fa fa-box text-white fa-2x"></span>
                                            </div>
                                        </div>
                                        <div class="col-8 col-md-12">
                                            <div class="total_number"><?php echo e($item_count); ?></div>
                                            <div class="card_title"><?php echo e(__('Total Advertisements')); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-12 mb-3">
                        <a href="<?php echo e(route('category.index')); ?>">
                            <div class="card h-100" style="width: 100%;">
                                <div class="item_for_sale d-flex">
                                    <div class="curtain"></div>
                                    <div class="row">
                                        <div class="col-4 col-md-12 ">
                                            <div class="svg_icon align-items-center d-flex justify-content-center me-3">
                                                <span class="fa fa-layer-group text-white fa-2x"></span>
                                            </div>
                                        </div>
                                        <div class="col-8 col-md-12">
                                            <div class="total_number"><?php echo e($categories_count); ?></div>
                                            <div class="card_title"><?php echo e(__('Total Categories')); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-12 mb-3">
                        <a href="<?php echo e(route('custom-fields.index')); ?>">
                            <div class="card h-100" style="width: 100%;">
                                <div class="properties_for_rent d-flex">
                                    <div class="curtain"></div>
                                    <div class="row">
                                        <div class="col-4 col-md-12 ">
                                            <div class="svg_icon align-items-center d-flex justify-content-center me-3">
                                                <span class="fab fa-wpforms text-white fa-2x"></span>
                                            </div>
                                        </div>
                                        <div class="col-8 col-md-12">
                                            <div class="total_number"><?php echo e($custom_field_count); ?></div>
                                            <div class="card_title"><?php echo e(__('Total Custom Fields')); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-6 col-sm-12">
                <div class="card h-100" style="width: 100%;">
                    <div class="card-header border-0 pb-0">
                        <h3 style="font-weight: 600"><?php echo e(__("Featured Sections")); ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="table-borderless table-striped" aria-describedby="mydesc"
                               id="table_list" data-toggle="table" data-url="<?php echo e(route('feature-section.show',1)); ?>"
                               data-click-to-select="true" data-search="true" data-toolbar="#toolbar"
                               data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                               data-fixed-number="1" data-trim-on-search="false" data-responsive="true"
                               data-escape="true"
                               data-sort-name="id" data-sort-order="desc" data-query-params="queryParams" data-mobile-responsive="true"
                               data-side-pagination="server"  data-pagination="true" data-page-size="3">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                                <th scope="col" data-field="style" data-formatter="styleImageFormatter"><?php echo e(__('Style')); ?></th>
                                <th scope="col" data-field="title" data-sortable="false"><?php echo e(__('Title')); ?></th>
                                <th scope="col" data-field="filter" data-sortable="false" data-formatter="filterTextFormatter"><?php echo e(__('Filters')); ?></th>
                                <th scope="col" data-field="min_price" data-sortable="true" data-visible="false"><?php echo e(__('Min Price')); ?></th>
                                <th scope="col" data-field="max_price" data-sortable="true" data-visible="false"><?php echo e(__('Max price')); ?></th>
                                <th scope="col" data-field="values_text" data-sortable="true" data-visible="false"><?php echo e(__('Value')); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
                <div class="card">
            <div class="card-header border-0 pb-0">
                <h3 style="font-weight: 600"><?php echo e(__("Recent Advertisements")); ?></h3>
            </div>
            <div class="card-body">
                <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                       data-toggle="table" data-url="<?php echo e(route('advertisement.show',1)); ?>" data-click-to-select="true"
                       data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                       data-fixed-columns="true" data-fixed-number="1" data-trim-on-search="false"
                       data-responsive="true" data-sort-name="id" data-sort-order="desc"
                       data-escape="true"
                       data-query-params="queryParams" data-mobile-responsive="true">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col" data-field="id" data-sortable="true"><?php echo e(__('ID')); ?></th>
                        <th scope="col" data-field="name" data-sortable="true"><?php echo e(__('Name')); ?></th>
                        <th scope="col" data-field="category.name" data-sortable="true"><?php echo e(__('Category')); ?></th>
                        <th scope="col" data-field="user.name" data-sortable="true"><?php echo e(__('Added By')); ?></th>
                        <th scope="col" data-field="price" data-sortable="true"><?php echo e(__('Price')); ?></th>
                        <th scope="col" data-field="status" data-sortable="true" data-formatter="itemStatusFormatter"><?php echo e(__('Status')); ?></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <input type="hidden" name="map_data" id="map_data" value="<?php echo e($items); ?>">
        
            <div class="col-md-12 col-sm-12">
                <div class="card map_title h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 style="font-weight: 600"><?php echo e(__('Most Viewed')); ?></h3>
                    </div>
                    <div class="card-body h-50">
                        <div id="world-map" style="width: 100%; height:400px"></div>
                    </div>
                </div>
            </div>
        
        <div class="row mb-10">
            <div class="col-md-6 col-sm-12">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="recent_list_heading"><?php echo e(__("Total Categories")); ?></div>
                    </div>
                    <div class="card-body mt-5">
                       <div class="card-body mt-5">
                            <?php if(!empty($category_item_count) && count($category_item_count) > 0): ?>
                                <div id="pie_chart"></div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <h5><?php echo e(__('No categories available to display')); ?></h5>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        let seriesData = <?php echo json_encode(array_values($category_item_count)); ?>;
        let hasData = Array.isArray(seriesData) && seriesData.length > 0 && seriesData.some(count => count > 0);

        if (hasData) {
            let options = {
                series: seriesData,
                chart: {
                    type: 'donut',
                    height: "700px"
                },
                labels: <?php echo json_encode($category_name); ?>,
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: '100%',
                            height: '300px',
                        },
                    }
                }],
                legend: {
                    show: true,
                    showForSingleSeries: false,
                    showForNullSeries: true,
                    showForZeroSeries: true,
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '18px',
                    fontFamily: 'Helvetica, Arial',
                    fontWeight: 400,
                    itemMargin: {
                        horizontal: 30,
                        vertical: 10
                    }
                }
            };
            let chart1 = new ApexCharts(document.querySelector("#pie_chart"), options);
            chart1.render();
        } else {
            document.getElementById('pie_chart').style.display = 'none';
            document.getElementById('no_data_msg').style.display = 'block';
        }
    </script>



    <!-- Existing JS for chart rendering -->

<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
<script>
    var mapData = JSON.parse($('#map_data').val());
        //  var currency_symbol = $('#currency_symbol').val();
        //  var featured='<div class="featured_tag"><div class="featured_lable">Featured</div>';
        var markerValues = mapData.map(function(item, index) {
    return {
        latLng: [parseFloat(item.latitude), parseFloat(item.longitude)],
        name: item.city,
        label: item.city,
        style: {
            fill: '#00B2CA',
            stroke: '#00000'
        },
        card: {
            content: '<div class="card_map">' +
                        '<div class="image-container">' +
                            '<img src="' + item.image + '" alt="' + item.name + '" class="object-fit-cover">' +
                        '</div>' +
                        '<div class="title mt-3">' + item.name + '</div>' +
                        '<div class="price mt-2">' + item.price + '</div>' +
                        '<div class="city mt-2">' +
                            '<i class="bi bi-geo-alt"></i> ' + item.city +
                        '</div>' +
                    '</div>'
        }
    };
});

            $('#world-map').vectorMap({
            map: 'world_mill',
            // scaleColors: ['#116D6E', '#116D6E'],
            backgroundColor: '#fffff',
            markerStyle: {
            initial: {
            strokeWidth: 1,
            stroke: '#383F47',
            fillOpacity: 1,
            r: 8,
            },
            onMarkerLabelShow: function(event, label, index) {
            // Add custom CSS classes to the label element
            label.addClass('custom-marker-label');
            },
            },
            markers: markerValues,
            series: {
            markers: [{
            // attribute: 'fill',
            scale: {}, // Empty scale object to be populated dynamically
            values: mapData.map(function(item) {
            return item.city;
            })
            }]
            },
            onMarkerTipShow: function(event, label, index) {
            var cardContent = markerValues[index].card.content;
            label.html(cardContent);
            }
            });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/home.blade.php ENDPATH**/ ?>