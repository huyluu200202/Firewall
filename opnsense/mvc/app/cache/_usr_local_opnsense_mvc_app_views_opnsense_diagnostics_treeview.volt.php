

 <style>
    .bootstrap-dialog-body {
        overflow-x: auto;
    }
    .modal-dialog,
    .modal-content {
        height: 80%;
    }

    .modal-body {
        height: calc(100% - 120px);
        overflow-y: scroll;
    }
    @media (min-width: 768px) {
        .modal-dialog {
            width: 90%;
        }
    }
</style>

<script>
    $( document ).ready(function() {
      $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
          $(".tab-icon").removeClass("fa-refresh");
          if ($("#"+e.target.id).data('tree-target') !== undefined) {
              $("#"+e.target.id).unbind('click').click(function(){
                ajaxGet($("#"+e.target.id).data('tree-endpoint'), {}, function (data, status) {
                    if (status == "success") {
                        update_tree(data, "#" + $("#"+e.target.id).data('tree-target'));
                    }
                });
              });
              if (!$("#"+e.target.id).hasClass("event-hooked")) {
                  $("#"+e.target.id).addClass("event-hooked")
                  $("#"+e.target.id).click();
              }
              $("#"+e.target.id).find(".tab-icon").addClass("fa-refresh");
          }

          $(window).trigger('resize');
      });

      /**
       * resize tree height
       */
      $(window).on('resize', function() {
          let new_height = $(".page-foot").offset().top -
                           ($(".page-content-head").offset().top + $(".page-content-head").height()) - 160;
          $(".treewidget").height(new_height);
          $(".treewidget").css('max-height', new_height + 'px');
      });


      /**
       * hook delayed live-search tree view
       */
      $(".tree_search").keyup(tree_delayed_live_search);

      // update history on tab state and implement navigation
      let selected_tab = window.location.hash != "" ? window.location.hash : "#<?= $default_tab ?>";
      $('a[href="' +selected_tab + '"]').click();
      $('.nav-tabs a').on('shown.bs.tab', function (e) {
          history.pushState(null, null, e.target.hash);
      });
      $(window).on('hashchange', function(e) {
          $('a[href="' + window.location.hash + '"]').click()
      });
    });
</script>

<style>
  .searchbox {
    margin: 8px;
  }

  .node-selected {
      font-weight: bolder;
  }
</style>


<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
<?php foreach ($tabs as $tab) { ?>
    <li>
      <a data-toggle="tab" href="#<?= $tab['name'] ?>" id="<?= $tab['name'] ?>_tab"
         data-tree-target="<?= $tab['name'] ?>Tree"
         data-tree-endpoint="<?= $tab['endpoint'] ?>">
          <?= $tab['caption'] ?> <i class="fa tab-icon "></i>
      </a>
    </li>
<?php } ?>
</ul>
<div class="tab-content content-box">
<?php foreach ($tabs as $tab) { ?>
    <div id="<?= $tab['name'] ?>" class="tab-pane fade in active">
      <div class="row">
          <section class="col-xs-12">
            <div class="content-box">
                <div class="searchbox">
                    <input
                        id="<?= $tab['name'] ?>Search"
                        type="text"
                        for="<?= $tab['name'] ?>Tree"
                        class="tree_search"
                        placeholder="<?= $lang->_('search') ?>"
                    ></input>
                </div>
                <div class="treewidget" style="padding: 8px; overflow-y: scroll; height:400px;" id="<?= $tab['name'] ?>Tree"></div>
              </div>
          </section>
      </div>
    </div>
<?php } ?>
</div>
