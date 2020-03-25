jQuery(document).ready(function($){

  /**
   * Init plugin
   */
  $('.wpcf7-upload_image').each(function() {

    $(this).Optimizer3k({
      targetSize: YR3K_UPLOADER_OPTIONS.targetSize,
      quality: +YR3K_UPLOADER_OPTIONS.quality,
      minQuality: +YR3K_UPLOADER_OPTIONS.minQuality,
      qualityStepSize: +YR3K_UPLOADER_OPTIONS.qualityStepSize,
      maxWidth: +YR3K_UPLOADER_OPTIONS.maxWidth,
      maxHeight: +YR3K_UPLOADER_OPTIONS.maxHeight,
      resize: YR3K_UPLOADER_OPTIONS.resize == 1,
      throwIfSizeNotReached: YR3K_UPLOADER_OPTIONS.throwIfSizeNotReached == 1,
      templatePreview: YR3K_UPLOADER_OPTIONS.templatePreview,
      templateDndArea: YR3K_UPLOADER_OPTIONS.templateDndArea,
    })
  })
})
