<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="large">
	<h1 class="title">
		<?php echo _('About'); ?>
	</h1>
	<div class="info">
		<div id="tablelist">
			<div class="row">
				<div class="cell"><b><?php echo _('Site'); ?></b>:</div>
				<div class="cell"><?php echo get_setting('fs_gen_site_title'); ?></div>
			</div>
			<?php if (get_setting('fs_about_admin_name')) : ?>
			<div class="row">
				<div class="cell"><b><?php echo _('Administrator'); ?></b>:</div>
				<div class="cell"><?php echo htmlspecialchars(get_setting('fs_about_admin_name')); ?></div>
			</div>
			<?php endif; ?>
			<?php if (get_setting('fs_about_admin_email')) : ?>
			<div class="row">
				<div class="cell"><b><?php echo _('Contact'); ?></b>:</div>
				<div class="cell"><a href="mailto:<?php echo htmlspecialchars(get_setting('fs_about_admin_email')); ?>"><?php echo htmlspecialchars(get_setting('fs_about_admin_email')); ?></a></div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="list">
	<div class="title"><?php echo _('About This Site'); ?></div>
	<div class="group">
		<div class="elemento">
			<?php if (get_setting('fs_about_message')) : ?>
				<p><?php echo get_setting('fs_about_message'); ?></p>
			<?php else : ?>
				<p>
					<?php echo get_setting('fs_gen_site_title'); ?> <?php echo _('is a manga and comic reading platform dedicated to providing quality content to our community. Our mission is to make manga and comics easily accessible to everyone by maintaining an organized and user-friendly platform.'); ?>
				</p>
			<?php endif; ?>
			<?php if (get_setting('fs_about_admin_email')) : ?>
				<p>
					<?php echo _('If you have any questions or feedback, please contact us using the information provided above.'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>
