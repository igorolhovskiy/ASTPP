<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
    $(document).ready(function() {
      
        build_grid("fs_sip_devices_grid","",<? echo $grid_fields; ?>,<? echo $grid_buttons; ?>);
        
        $("#fssipdevice_search_btn").click(function(){
	  
	post_request_for_search("fs_sip_devices_grid","","freeswith_search");
        });        
        $("#id_reset").click(function(){
            clear_search_request("fs_sip_devices_grid","");
        });
        $('.checkall').click(function () { 
                $('.chkRefNos').attr('checked', this.checked); //if you want to select/deselect checkboxes use this
        });

      var updatePeriod = 5000;
      setTimeout(function updateState() {
        updateStateDevice()
          .then(function(){
            setTimeout(updateState, updatePeriod);
          })
          .catch(function(error){
            console.log('error get status', error);
            setTimeout(updateState, updatePeriod)
          });
      }, 0);
    });

    function updateStateDevice() {
      var apiUrl = '<?php echo $state_api_point_url; ?>';
      var iconRegistered = '<?php echo $icon_registered; ?>';
      var iconUnregistered = '<?php echo $icon_unregistered; ?>';
      var numbers = $('#fs_sip_devices_grid tr td .number-sipdevice');
      var promises = [];
      $.each(numbers, function(index, item) {
        var num = $(item).html();
        promises.push(
          new Promise(function(resolve, reject) {
            $.ajax({
              url: apiUrl + num,
              success: function(data) {
                var result = JSON.parse(data);
                if (result.success) {
                  if (result.state == 1) {
                    $(item).closest('tr').find('.state-sipdevice').html(iconRegistered);
                  } else {
                    $(item).closest('tr').find('.state-sipdevice').html(iconUnregistered);
                  }
                }
                resolve();
              },
              error: function(error) {
                reject(error);
              }
            });
          })
        );
      });
      return Promise.all(promises);
    }
</script>

<? // echo "<pre>"; print_r($grid_fields); exit;?>

<? endblock() ?>

<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>        
<section class="slice color-three">
	<div class="w-section inverse no-padding">
    	<div class="container">
   	    <div class="row">
            	<div class="portlet-content"  id="search_bar" style="cursor:pointer; display:none">
                    	<?php echo $form_search; ?>
    	        </div>
            </div>
        </div>
    </div>
</section>

<section class="slice color-three padding-b-20">
	<div class="w-section inverse no-padding">
    	<div class="container">
        	<div class="row">
                <div class="col-md-12">      
                        <form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">
                            <table id="fs_sip_devices_grid" align="left" style="display:none;"></table>
                        </form>
                </div>  
            </div>
        </div>
    </div>
</section>



<? endblock() ?>	

<? end_extend() ?>  
