<?php
echo '<div class="wrap">';
echo '<h1>'; ?><?php echo esc_html(__('Images Optimize & Upload - Settings', YR3K_UPLOAD_REGISTRATION_NAME)); ?><?php echo '</h1>';
echo '<form method="post" action="options.php">';

$htmlAttrSelected = 'selected="selected"';

$selectedResize = get_option('yr-images-optimize-upload-resize', true);
$throwIfSizeNotReached = get_option('yr-images-optimize-upload-throwIfSizeNotReached');
$removeFileAfterSend = get_option('yr-images-optimize-upload-removeFileAfterSend', 1);

$templatePreview = esc_html(get_option('yr-images-optimize-upload-template', Yr3kUploaderSettings::getTemplatePreview()));
$templateDndArea = esc_html(get_option('yr-images-optimize-upload-template-dnd', Yr3kUploaderSettings::getTemplateDndArea()));

settings_fields(YR3K_UPLOAD_REGISTRATION_NAME);
do_settings_sections(YR3K_UPLOAD_REGISTRATION_NAME);
?>
<table class="form-table">
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-maxFiles"><?php echo esc_html(__('Maximum Files', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-maxFiles" id="yr-images-optimize-upload-maxFiles" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 3" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-maxFiles', 3)); ?>" >
        <p class="description"><?php echo esc_html(__('The maximum number of images that users can upload in one form.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-targetSize"><?php echo esc_html(__('Output Target File Size', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-targetSize" id="yr-images-optimize-upload-targetSize" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 0.25" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-targetSize')); ?>" >
        <p class="description"><?php echo esc_html(__('The output file size of the image in MB. If the image size is greater than the output target file size after a compression, the image is compressed with a lower quality. This happens again and again until the next compression would make the size &lt;= the output target file size or the quality would be less than the minimum quality. Default value: 0.2 (200 KB).', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-quality"><?php echo esc_html(__('Quality', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-quality" id="yr-images-optimize-upload-quality" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 0.75" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-quality')); ?>"><br>
        <p class="description"><?php echo esc_html(__('The initial quality to compress the image at. Default value: 0.75 (75% compression).', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-minQuality"><?php echo esc_html(__('Minimum Quality', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-minQuality" id="yr-images-optimize-upload-minQuality" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 0.5" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-minQuality')); ?>">
        <p class="description"><?php echo esc_html(__('The minimum quality allowed for an image compression. This is only relevant if the initial compression does not make the image size &lt;= the Desired Output File Size.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-qualityStepSize"><?php echo esc_html(__('Quality Step Size', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-qualityStepSize" id="yr-images-optimize-upload-qualityStepSize" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 0.1" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-qualityStepSize')); ?>">
        <p class="description"><?php echo esc_html(__('The amount to try reducing the quality by in each iteration, if the image size is still &gt; the Desired Output File Size.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-maxWidth"><?php echo esc_html(__('Maximum Width', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-maxWidth" id="yr-images-optimize-upload-maxWidth" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 1920" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-maxWidth')); ?>">
        <p class="description"><?php echo esc_html(__('The maximum width of the output image. The value is in pixels. Please, use only numbers.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-maxHeight"><?php echo esc_html(__('Maximum Height', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <input name="yr-images-optimize-upload-maxHeight" id="yr-images-optimize-upload-maxHeight" type="text" placeholder="<?php echo esc_html(__('Default:', YR3K_UPLOAD_REGISTRATION_NAME)); ?> 1920" value="<?php echo esc_attr(get_option('yr-images-optimize-upload-maxHeight')); ?>">
        <p class="description"><?php echo esc_html(__('The maximum height of the output image. The value is in pixels. Please, use only numbers.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-resize"><?php echo esc_html(__('Resize', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <select name="yr-images-optimize-upload-resize" id="yr-images-optimize-upload-resize">
          <option value="1" <?php echo (1 == $selectedResize) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('Yes', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
          <option value="0" <?php echo (0 == $selectedResize) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('No', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
        </select>
        <p class="description"><?php echo esc_html(__('Whether the image should be resized to within the bounds set by Maximum Width and Maximum Height (maintains the aspect ratio).', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-throwIfSizeNotReached"><?php echo esc_html(__('Show Errors in Browser Console', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <select name="yr-images-optimize-upload-throwIfSizeNotReached" id="yr-images-optimize-upload-throwIfSizeNotReached">
          <option value="0" <?php echo (0 == $throwIfSizeNotReached) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('No', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
          <option value="1" <?php echo (1 == $throwIfSizeNotReached) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('Yes', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
        </select>
        <p class="description"><?php echo esc_html(__('Whether to throw an Error if the Desired Output File Size is not reached.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-removeFileAfterSend"><?php echo esc_html(__('Remove Temp Files from Server', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
      <td>
        <select name="yr-images-optimize-upload-removeFileAfterSend" id="yr-images-optimize-upload-removeFileAfterSend">
          <option value="0" <?php echo (0 == $removeFileAfterSend) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('No', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
          <option value="1" <?php echo (1 == $removeFileAfterSend) ? $htmlAttrSelected : ''; ?>><?php echo esc_html(__('Yes', YR3K_UPLOAD_REGISTRATION_NAME)); ?></option>
        </select>
        <p class="description"><?php echo esc_html(__('Whether the temporary files should be removed or saved on the server. If you want to keep the files on the server, use Contact Form 7 Database Addon – CFDB7 to access them.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-template-dnd"><?php echo esc_html(__('Drag & Drop Area Template', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
    <td>
      <fieldset>
        <textarea name="yr-images-optimize-upload-template-dnd"
                  rows="10"
                  cols="50"
                  id="yr-images-optimize-upload-template-dnd"
                  class="large-text code"
                  placeholder='<?php echo Yr3kUploaderSettings::getTemplateDndArea(); ?>'
        ><?php echo $templateDndArea; ?></textarea>
        <p class="description"><?php echo esc_html(__('The template which is used to generate drag and drop area on the front end. Be careful when editing this field.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </fieldset></td>
  </tr>
  <tr>
    <th scope="row"><label for="yr-images-optimize-upload-template"><?php echo esc_html(__('Images Preview Template', YR3K_UPLOAD_REGISTRATION_NAME)); ?></label></th>
    <td>
      <fieldset>
        <textarea name="yr-images-optimize-upload-template"
                  rows="10"
                  cols="50"
                  id="yr-images-optimize-upload-template"
                  class="large-text code"
                  placeholder='<?php echo Yr3kUploaderSettings::getTemplatePreview(); ?>'
        ><?php echo $templatePreview; ?></textarea>
        <p class="description"><?php echo esc_html(__('The template which is used to generate images previews on the front end. This field can not be empty.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{photoName}} - <?php echo esc_html(__('Uploaded image file name.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{beforeSize}} - <?php echo esc_html(__('Original image size. In MB.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{afterSize}} - <?php echo esc_html(__('Optimized image size. In MB.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{startWidth}} - <?php echo esc_html(__('Original image width.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{startHeight}} - <?php echo esc_html(__('Original image height.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{endWidth}} - <?php echo esc_html(__('Optimized image width.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{endHeight}} - <?php echo esc_html(__('Optimized image height.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{txtDelete}} - <?php echo esc_html(__('Delete tooltip text.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{txtInfoFileOrigin}} - <?php echo esc_html(__('Original Size text.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
        <p class="description">{{txtInfoFileCompress}} - <?php echo esc_html(__('Compressed text.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></p>
      </fieldset></td>
  </tr>
</table>

<h2><?php echo esc_html(__('Limitations', YR3K_UPLOAD_REGISTRATION_NAME)); ?></h2>
<h4><?php echo esc_html(__('There are several limitations for this plugin:', YR3K_UPLOAD_REGISTRATION_NAME)); ?></h4>
<ul>
  <li><?php echo esc_html(__('* This plugin supports the following file formats: PNG, JPG, JPEG, GIF, BMP.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></li>
  <li><?php echo esc_html(__('* When working with animated GIF, the compressed image will no longer animate.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></li>
  <li><?php echo esc_html(__('* When working with PNG with transparent background, the compressed image will lose transparency and result in black background.', YR3K_UPLOAD_REGISTRATION_NAME)); ?></li>
</ul>
<?php submit_button();
echo '</form></div>';
