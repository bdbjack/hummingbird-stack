<?php defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' ); ?>
<style type="text/css">html{min-height:100%;background-color:#fff;background-repeat:no-repeat;background-image:-webkit-gradient(linear,0 0,0 100%,from(#f6f6f6),color-stop(50%,#fff),to(#fff));background-image:-webkit-linear-gradient(#f6f6f6,#fff 50%,#fff);background-image:-ms-linear-gradient(#f6f6f6,#fff 50%,#fff);background-image:-o-linear-gradient(#f6f6f6,#fff 50%,#fff);background-image:-moz-linear-gradient(#f6f6f6,#fff 50%,#fff);background-image:linear-gradient(#f6f6f6,#fff 50%,#fff);background-attachment:fixed}body{background:rgba(0,0,0,0);background-color:rgba(0,0,0,0)}#site-not-enabled-error{margin-top:75px}.panel.panel-danger,.panel.panel-danger>.panel-heading,.panel.panel-danger>.panel-footer,.panel.panel-danger>.panel-body,.panel.panel-danger>.table,.panel.panel-danger>.table-responsive{border-radius:0}.panel.panel-danger{border:1px solid #ccccca;webkit-box-shadow:0 0 15px 0 rgba(51,51,51,.25);-moz-box-shadow:0 0 15px 0 rgba(51,51,51,.25);-ms-box-shadow:0 0 15px 0 rgba(51,51,51,.25);-o-box-shadow:0 0 15px 0 rgba(51,51,51,.25);box-shadow:0 0 15px 0 rgba(51,51,51,.25);background-color:#f5f5f5;background-repeat:no-repeat;background-image:-webkit-gradient(linear,0 0,0 100%,from(#fff),color-stop(80%,#f5f5f5),to(#f5f5f5));background-image:-webkit-linear-gradient(#fff,#f5f5f5 80%,#f5f5f5);background-image:-ms-linear-gradient(#fff,#f5f5f5 80%,#f5f5f5);background-image:-o-linear-gradient(#fff,#f5f5f5 80%,#f5f5f5);background-image:-moz-linear-gradient(#fff,#f5f5f5 80%,#f5f5f5);background-image:linear-gradient(#fff,#f5f5f5 80%,#f5f5f5)}.panel.panel-danger>.panel-heading{background-color:#a82d31;background-repeat:repeat-x;background-image:-ms-linear-gradient(top,#c43d53,#a82d31);background-image:-webkit-gradient(linear,left top,left bottom,color-stop(0%,#c43d53),color-stop(100%,#a82d31));background-image:-webkit-linear-gradient(top,#c43d53,#a82d31);background-image:-o-linear-gradient(top,#c43d53,#a82d31);background-image:-moz-linear-gradient(top,#c43d53,#a82d31);background-image:linear-gradient(top,#c43d53,#a82d31);-webkit-box-shadow:inset 0 1px 0 0 #c43d53;-moz-box-shadow:inset 0 1px 0 0 #c43d53;-ms-box-shadow:inset 0 1px 0 0 #c43d53;-o-box-shadow:inset 0 1px 0 0 #c43d53;box-shadow:inset 0 1px 0 0 #c43d53;-webkit-text-shadow:none;-moz-text-shadow:none;-ms-text-shadow:none;-o-text-shadow:none;text-shadow:none;border-bottom:1px solid #ccc;color:#fff}.panel.panel-danger>.panel-heading>*{color:#fff}.panel.panel-danger>.panel-body>*:last-child{margin-bottom:0}</style>
<div class="container">
	<div class="row">
		<div class="col-xs-12">
			<div class="panel panel-danger" style="margin-top: 50px;">
				<div class="panel-heading">
					<h4>Error</h4>
				</div>
				<div class="panel-body">
					<p>Received the following error:</p>
					<pre><?php echo $fbo->message; ?></pre>
					<?php
						if ( is_array( $fbo->data ) && array_key_exists( 'file', $fbo->data ) ) {
					?>
					<p>File:</p>
					<pre><?php echo $fbo->data['file']; ?></pre>
					<p>Line:</p>
					<pre><?php echo $fbo->data['line']; ?></pre>
					<?php
						}
					?>
				</div>
				<?php
					$bt = debug_backtrace();
					array_shift( $bt );
					array_shift( $bt );
					array_shift( $bt );
					if ( HC::canLoop( $bt ) ) {
				?>
				<div class="table-responsive">
					<table class="table table-bordered">
						<tbody>
						<?php
						foreach ( $bt as $trace ) {
				?>
						<tr>
							<th>File</th>
							<th>Line</th>
							<th>Class</th>
							<th>Function</th>
						</tr>
						<tr>
							<td><?php echo HC::obfuscateWebDirectory( HC::getArrayKey( 'file', $trace, 'NULL' ) ); ?></td>
							<td><?php echo HC::absInt( HC::getArrayKey( 'line', $trace, 0 ) ); ?></td>
							<td><?php echo HC::getArrayKey( 'class', $trace ); ?></td>
							<td><?php echo HC::getArrayKey( 'function', $trace ); ?></td>
						</tr>
						<tr class="hidden-xs hidden-sm">
							<td colspan="4"><pre><?php echo HC::showFileContents( HC::getArrayKey( 'file', $trace ), HC::getArrayKey( 'line', $trace ) ); ?></pre></td>
						</tr>
				<?php
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
					}
				?>
				<div class="panel-footer">
					<p>For more information please contact the system administrator.</p>
				</div>
			</div>
		</div>
	</div>
</div>