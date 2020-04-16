<div class="control-box">
  <fieldset>
    <legend><?php echo $legend; ?></legend>
    <table class="form-table">
      <tbody>
      <tr>
        <th scope="row"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></legend>
            <label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'contact-form-7')); ?></label>
          </fieldset>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo esc_attr($prefix.'-name'); ?>"><?php echo esc_html(__('Name', 'contact-form-7')); ?></label></th>
        <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($prefix.'-name'); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo esc_attr($prefix.'-max-file'); ?>"><?php echo esc_html(__('Max file upload', 'contact-form-7')); ?></label></th>
        <td><input type="text" name="max-file" class="filetype oneline option" placeholder="3 default" id="<?php echo esc_attr($prefix.'-max-file'); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo esc_attr($prefix.'-id'); ?>"><?php echo esc_html(__('Id attribute', 'contact-form-7')); ?></label></th>
        <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($prefix.'-id'); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="<?php echo esc_attr($prefix.'-class'); ?>"><?php echo esc_html(__('Class attribute', 'contact-form-7')); ?></label></th>
        <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($prefix.'-class'); ?>" /></td>
      </tr>
      </tbody>
    </table>
  </fieldset>
</div>

<div class="insert-box">
  <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
  <div class="submitbox">
    <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
  </div>
  <!--br class="clear" />
  <p class="description mail-tag">
    <label for="<?php echo esc_attr($prefix.'-mailtag'); ?>"><?php echo sprintf(esc_html(__('To attach the file uploaded through this field to mail, you need to insert the corresponding mail-tag (%s) into the File Attachments field on the Mail tab.', 'contact-form-7')), '<strong><span class="mail-tag"></span></strong>'); ?>
      <input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($prefix.'-mailtag'); ?>" /></label>
  </p-->
</div>
