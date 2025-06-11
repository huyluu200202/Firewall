

<script>
    'use strict';

    $( document ).ready(function () {
        let grid_service = $("#grid-service").UIBootgrid({
            search:'/api/core/service/search',
            options:{
                multiSelect: false,
                rowSelect: true,
                selection: false,
                formatters:{
                    commands: function (column, row) {
                        if (row['locked']) {
                            return '<button type="button" class="btn btn-xs btn-default command-restart" data-toggle="tooltip" title="<?= $lang->_('Restart') ?>" data-row-id="' + row.id + '"><span class="fa fa-repeat fa-fw"></span></button>';
                        } else if (row['running']) {
                            return '<button type="button" class="btn btn-xs btn-default command-restart" data-toggle="tooltip" title="<?= $lang->_('Restart') ?>" data-row-id="' + row.id + '"><span class="fa fa-repeat fa-fw"></span></button>' +
                                '<button type="button" class="btn btn-xs btn-default command-stop" data-toggle="tooltip" title="<?= $lang->_('Stop') ?>" data-row-id="' + row.id + '"><span class="fa fa-stop fa-fw"></span></button>';
                        } else {
                            return '<button type="button" class="btn btn-xs btn-default command-start" data-toggle="tooltip" title="<?= $lang->_('Start') ?>" data-row-id="' + row.id + '"><span class="fa fa-play fa-fw"></span></button>';
                        }
                    },
                    status: function (column, row) {
                        if (row['running']) {
                            return '<span class="label label-opnsense label-opnsense-xs label-success pull-right" data-toggle="tooltip" title="<?= $lang->_('Running') ?>"><i class="fa fa-play fa-fw"></i></span>';
                        } else {
                            return '<span class="label label-opnsense label-opnsense-xs label-danger pull-right" data-toggle="tooltip" title="<?= $lang->_('Stopped') ?>"><i class="fa fa-stop fa-fw"></i></span>';
                        }
                    }
                }
            }
        });
        grid_service.on('loaded.rs.jquery.bootgrid', function () {
            $('[data-toggle="tooltip"]').tooltip();
            let ids = $("#grid-service").bootgrid("getCurrentRows");
            if (ids.length > 0) {
                $("#grid-service").bootgrid('select', [ids[0].name]);
            }
            $('.command-stop').click(function () {
                $(this).toggleClass('disabled');
                $(this).children().toggleClass('fa-stop fa-spinner fa-pulse');
                ajaxCall("/api/core/service/stop/" + $(this).data('row-id'), {}, function () {
                    $('#grid-service').bootgrid('reload');
                });
            });
            $('.command-start').click(function () {
                $(this).toggleClass('disabled');
                $(this).children().toggleClass('fa-start fa-spinner fa-pulse');
                ajaxCall("/api/core/service/start/" + $(this).data('row-id'), {}, function () {
                    $('#grid-service').bootgrid('reload');
                });
            });
            $('.command-restart').click(function () {
                $(this).toggleClass('disabled');
                $(this).children().toggleClass('fa-repeat fa-spinner fa-pulse');
                ajaxCall("/api/core/service/restart/" + $(this).data('row-id'), {}, function () {
                    $('#grid-service').bootgrid('reload');
                });
            });
        });
    });

</script>

<div class="tab-content content-box __mb">
    <table id="grid-service" class="table table-condensed table-hover table-striped table-responsive">
        <thead>
          <tr>
            <th data-column-id="id" data-type="string" data-sortable="false" data-identifier="true" data-visible="false"><?= $lang->_('ID') ?></th>
            <th data-column-id="pad" data-type="string" data-sortable="false" data-width="1em"></th>
            <th data-column-id="name" data-type="string"><?= $lang->_('Name') ?></th>
            <th data-column-id="description" data-type="string"><?= $lang->_('Description') ?></th>
            <th data-column-id="locked" data-type="string" data-sortable="false" data-visible="false"></th>
            <th data-column-id="running" data-type="string" data-width="3em" data-formatter="status" data-sortable="false"></th>
            <th data-column-id="commands" data-width="5em" data-formatter="commands" data-sortable="false"></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
