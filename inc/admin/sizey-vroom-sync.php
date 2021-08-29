<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

?>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<form action="" method="post">
			<?php wp_nonce_field( 'sizey-vroom-config-action', 'vroom-config-nonce-field' ); ?>
			<table class="form-table editcomment">
<tr>
	<td>
		<input type="submit" id="vroom-sizey-button-sync"
			   name="vroom-sizey-button-sync"
			   value="Synchronize products to Sizey portal" class="button button-primary button-large" />
	</td>
</tr>
</table>
</form>
</div>
</div>
