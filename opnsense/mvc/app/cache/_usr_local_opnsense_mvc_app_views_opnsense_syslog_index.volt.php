

<script>

    $( document ).ready(function() {
        $("#grid-destinations").UIBootgrid(
            {   search:'/api/syslog/settings/searchDestinations',
                get:'/api/syslog/settings/getDestination/',
                set:'/api/syslog/settings/setDestination/',
                add:'/api/syslog/settings/addDestination/',
                del:'/api/syslog/settings/delDestination/',
                toggle:'/api/syslog/settings/toggleDestination/'
            }
        );
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (e.target.id === 'statistics') {
                $("#grid-statistics").UIBootgrid({
                    search: '/api/syslog/service/stats/'
                });
            }
        });
        /**
         * Reconfigure syslog
         */
        $("#reconfigureAct").SimpleActionButton();
        updateServiceControlUI('syslog');

        $("#destination\\.transport").change(function(){
            let transport_type = $(this).val();
            $(".transport_type").each(function(){
                if ($(this).hasClass("transport_type_" + transport_type)) {
                    $(this).closest("tr").show();
                } else {
                    $(this).closest("tr").hide();
                }
            });
        });
    });

</script>

<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" id="destinations" href="#tab_destinations"><?= $lang->_('Destinations') ?></a></li>
    <li><a data-toggle="tab" id="statistics" href="#tab_statistics"><?= $lang->_('Statistics') ?></a></li>
</ul>
<div class="tab-content content-box">
    <div id="tab_destinations" class="tab-pane fade in active">
        <!-- tab page "destinations" -->
        <table id="grid-destinations" class="table table-condensed table-hover table-striped table-responsive" data-editDialog="DialogDestination" data-editAlert="syslogChangeMessage">
            <thead>
            <tr>
                <th data-column-id="enabled" data-width="6em" data-type="string" data-formatter="rowtoggle"><?= $lang->_('Enabled') ?></th>
                <th data-column-id="transport" data-type="string"><?= $lang->_('Transport') ?></th>
                <th data-column-id="hostname" data-type="string"><?= $lang->_('Hostname') ?></th>
                <th data-column-id="description" data-type="string"><?= $lang->_('Description') ?></th>
                <th data-column-id="commands" data-width="7em" data-formatter="commands" data-sortable="false"><?= $lang->_('Commands') ?></th>
                <th data-column-id="uuid" data-type="string" data-identifier="true"  data-visible="false"><?= $lang->_('ID') ?></th>
            </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
            <tr>
                <td></td>
                <td>
                    <button data-action="add" type="button" class="btn btn-xs btn-primary"><span class="fa fa-fw fa-plus"></span></button>
                    <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default"><span class="fa fa-fw fa-trash-o"></span></button>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
    <div id="tab_statistics" class="tab-pane fade in">
        <table id="grid-statistics" class="table table-condensed table-hover table-striped table-responsive">
            <thead>
                <tr>
                    <th data-column-id="SourceName" data-type="string"><?= $lang->_('SourceName') ?></th>
                    <th data-column-id="SourceId" data-type="string"><?= $lang->_('SourceId') ?></th>
                    <th data-column-id="SourceInstance" data-type="string"><?= $lang->_('SourceInstance') ?></th>
                    <th data-column-id="State" data-type="string"><?= $lang->_('State') ?></th>
                    <th data-column-id="Type" data-type="string"><?= $lang->_('Type') ?></th>
                    <th data-column-id="Number" data-type="numeric"><?= $lang->_('Number') ?></th>
                    <th data-column-id="Description" data-type="string"><?= $lang->_('Description') ?></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div class="col-md-12">
        <div id="syslogChangeMessage" class="alert alert-info" style="display: none" role="alert">
            <?= $lang->_('After changing settings, please remember to apply them with the button below') ?>
        </div>
        <hr/>
        <button class="btn btn-primary" id="reconfigureAct"
                data-endpoint='/api/syslog/service/reconfigure'
                data-label="<?= $lang->_('Apply') ?>"
                data-service-widget="syslog"
                data-error-title="<?= $lang->_('Error reconfiguring syslog') ?>"
                type="button"
        ></button>
        <br/><br/>
    </div>
</div>



<?= $this->partial('layout_partials/base_dialog', ['fields' => $formDialogDestination, 'id' => 'DialogDestination', 'label' => $lang->_('Edit destination')]) ?>
