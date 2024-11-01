<h2><?php _e('Multi Order Settings', 'woo-multi-order'); ?></h2>
<?php if(	(isset($_POST['enableMultiOrder']) && !empty($_POST['enableMultiOrder'])) 
					|| (isset($_POST['dayDiff']) && !empty($_POST['dayDiff'])) 
					|| (isset($_POST['customOrderPermit']) && !empty($_POST['customOrderPermit']))){
	update_option('dayDifference',$_POST['dayDiff']);
	update_option('customOrderPermit',$_POST['customOrderPermit']);
	update_option('enableMultiOrder',$_POST['enableMultiOrder']);
	$successMsg = '<div class="updated"><p>Settings Saved Successfully</p></div>';
	echo $successMsg;
} ?>
<form action="" method="post">
	<table style="width:80%;">
		<?php
			$enableMultiOrder = get_option('enableMultiOrder');	
			$enableMultiOrderValue = ($enableMultiOrder==1)?'checked="checked"':''; 
		?>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Enable Multiorder.', 'woo-multi-order'); ?></label></td>
			<td style="width:50%;"><input type="checkbox" value="1" name="enableMultiOrder" <?php echo $enableMultiOrderValue; ?>></td>
		</tr>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Order Delivery Day Difference', 'woo-multi-order'); ?></label></td>
			<td style="width:50%;">
											<select name="dayDiff">
												<option value=""><?php _e('Select Day Difference', 'woo-multi-order'); ?></option>
												<?php for($i=1;$i<=10;$i++){ 
													$dayDifference = get_option('dayDifference');	
													$selectDays = ($dayDifference == $i)?'selected="selected"':''; 	
												?>
													<option value="<?php echo $i; ?>" <?php echo $selectDays; ?>><?php echo $i; ?></option>
												<?php } ?>
												
											</select>
			</td>
		</tr>
		<?php
			$customOrderPermit = get_option('customOrderPermit');	
			$checkValue = ($customOrderPermit==1)?'checked="checked"':''; 
		?>
		<tr>
			<td style="width:30%;"><label style="font-weight:bold;font-size:14px;float:left;"><?php _e('Customer can choose custom delivery date.', 'woo-multi-order'); ?></label></td>
			<td style="width:50%;"><input type="checkbox" value="1" name="customOrderPermit" <?php echo $checkValue; ?>></td>
		</tr>
		<tr>
			<td style="width:30%;"><br/><button class="button button-primary button-highlighted thickbox"><?php _e('Save Settings', 'woo-multi-order'); ?></button></td>
			<td style="width:50%;"></td>
		</tr>
	</table>
</form>