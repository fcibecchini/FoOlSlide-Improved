<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="list">
	<div class="title"><?php echo about_label('About'); ?></div>
	<div class="group">
		<div id="tablelist">
			<div class="row">
				<div class="cell"><b><?php echo about_label('Site'); ?></b></div>
				<div class="cell"><?php echo get_setting('fs_gen_site_title'); ?></div>
			</div>
			<?php if (get_setting('fs_about_admin_name')) : ?>
			<div class="row">
				<div class="cell"><b><?php echo about_label('Administrator'); ?></b></div>
				<div class="cell"><?php echo htmlspecialchars(get_setting('fs_about_admin_name')); ?></div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="list">
	<div class="title"><?php echo about_label('About This Site'); ?></div>
	<div class="group">
		<div class="elemento">
			<?php if (get_setting('fs_about_message')) : ?>
				<?php echo get_setting('fs_about_message'); ?>
			<?php else : ?>
				<p>
					<?php echo get_setting('fs_gen_site_title'); ?> <?php echo _('is a manga and comic reading platform dedicated to providing quality content to our community. Our mission is to make manga and comics easily accessible to everyone by maintaining an organized and user-friendly platform.'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php if ($about_contact_email) : ?>
<div class="list" id="contact-form">
	<div class="title"><?php echo _('Contact Us'); ?></div>
	<div class="group">
		<div class="elemento" style="color: #d7dce5;">
			<?php $contact_name_error = function_exists('form_error') ? form_error('contact_name') : ''; ?>
			<?php $contact_email_error = function_exists('form_error') ? form_error('contact_email') : ''; ?>
			<?php $contact_subject_error = function_exists('form_error') ? form_error('contact_subject') : ''; ?>
			<?php $contact_message_error = function_exists('form_error') ? form_error('contact_message') : ''; ?>
			<p style="color: #d7dce5;">
				<?php echo _('If you have any questions or feedback, please contact us using the information provided above.'); ?>
			</p>
			<?php echo form_open('about#contact-form'); ?>
				<p>
					<label for="contact_name" style="color: #d7dce5; display: block;"><?php echo _('Name'); ?></label>
					<input type="text" name="contact_name" id="contact_name" value="<?php echo htmlspecialchars($about_contact_form['name']); ?>" required="required" aria-required="true" aria-invalid="<?php echo $contact_name_error ? 'true' : 'false'; ?>" style="width: 100%; max-width: 42rem; background: #1f2733; border: 1px solid <?php echo $contact_name_error ? '#c96b7a' : '#3a4250'; ?>; border-radius: 8px; color: #f0f0f0; font: inherit; padding: 9px 10px; text-align: left;" />
					<?php if ($contact_name_error) : ?><span style="color: #ff9b9b;"><?php echo $contact_name_error; ?></span><?php endif; ?>
				</p>
				<p>
					<label for="contact_email" style="color: #d7dce5; display: block;"><?php echo _('Email'); ?></label>
					<input type="email" name="contact_email" id="contact_email" value="<?php echo htmlspecialchars($about_contact_form['email']); ?>" required="required" aria-required="true" aria-invalid="<?php echo $contact_email_error ? 'true' : 'false'; ?>" style="width: 100%; max-width: 42rem; background: #1f2733; border: 1px solid <?php echo $contact_email_error ? '#c96b7a' : '#3a4250'; ?>; border-radius: 8px; color: #f0f0f0; font: inherit; padding: 9px 10px; text-align: left;" />
					<?php if ($contact_email_error) : ?><span style="color: #ff9b9b;"><?php echo $contact_email_error; ?></span><?php endif; ?>
				</p>
				<p>
					<label for="contact_subject" style="color: #d7dce5; display: block;"><?php echo _('Subject'); ?></label>
					<input type="text" name="contact_subject" id="contact_subject" value="<?php echo htmlspecialchars($about_contact_form['subject']); ?>" required="required" aria-required="true" aria-invalid="<?php echo $contact_subject_error ? 'true' : 'false'; ?>" style="width: 100%; max-width: 42rem; background: #1f2733; border: 1px solid <?php echo $contact_subject_error ? '#c96b7a' : '#3a4250'; ?>; border-radius: 8px; color: #f0f0f0; font: inherit; padding: 9px 10px; text-align: left;" />
					<?php if ($contact_subject_error) : ?><span style="color: #ff9b9b;"><?php echo $contact_subject_error; ?></span><?php endif; ?>
				</p>
				<p>
					<label for="contact_message" style="color: #d7dce5; display: block;"><?php echo _('Message'); ?></label>
					<textarea name="contact_message" id="contact_message" rows="8" required="required" aria-required="true" aria-invalid="<?php echo $contact_message_error ? 'true' : 'false'; ?>" style="width: 100%; max-width: 42rem; background: #1f2733; border: 1px solid <?php echo $contact_message_error ? '#c96b7a' : '#3a4250'; ?>; border-radius: 8px; color: #f0f0f0; font: inherit; padding: 9px 10px; text-align: left;"><?php echo htmlspecialchars($about_contact_form['message']); ?></textarea>
					<?php if ($contact_message_error) : ?><span style="color: #ff9b9b;"><?php echo $contact_message_error; ?></span><?php endif; ?>
				</p>
				<p style="display: none;">
					<label for="contact_website"><?php echo _('Website'); ?></label><br />
					<input type="text" name="contact_website" id="contact_website" value="<?php echo htmlspecialchars($about_contact_form['website']); ?>" autocomplete="off" tabindex="-1" />
				</p>
				<p>
					<input type="submit" value="<?php echo _('Send Message'); ?>" style="background: #912F3F; border: none; border-radius: 999px; box-shadow: 0 2px 6px rgba(0,0,0,.25); color: #fff; cursor: pointer; font-size: 15px; font-weight: 700; height: 42px; padding: 10px 18px;" />
				</p>
				<?php echo get_notice_toasts('dazen-skin', 'inline'); ?>
			<?php echo form_close(); ?>
			<?php if (!empty($about_contact_focus_form)) : ?>
				<script>
				(function() {
					var container = document.getElementById('contact-form');
					if (!container) return;
					var target = container.querySelector('[aria-invalid="true"]') || container;
					if (target && typeof target.scrollIntoView === 'function') {
						target.scrollIntoView({behavior: 'auto', block: 'start'});
					}
					if (target && target !== container && typeof target.focus === 'function') {
						target.focus();
					}
				})();
				</script>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>
