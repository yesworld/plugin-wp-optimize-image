jQuery(document).ready(function($){

  function getNumberOption(value, fallback) {
    var number = parseFloat(value);

    if (isNaN(number)) {
      return fallback;
    }

    return number;
  }

  /**
   * Init plugin
   */
  $('.wpcf7-upload_image').each(function() {

    $(this).Optimizer3k({
      targetSize: getNumberOption(YR3K_UPLOADER_OPTIONS.targetSize, 0.25),
      quality: getNumberOption(YR3K_UPLOADER_OPTIONS.quality, 0.75),
      minQuality: getNumberOption(YR3K_UPLOADER_OPTIONS.minQuality, 0.5),
      qualityStepSize: getNumberOption(YR3K_UPLOADER_OPTIONS.qualityStepSize, 0.1),
      maxWidth: getNumberOption(YR3K_UPLOADER_OPTIONS.maxWidth, 1920),
      maxHeight: getNumberOption(YR3K_UPLOADER_OPTIONS.maxHeight, 1920),
      resize: YR3K_UPLOADER_OPTIONS.resize == 1,
      throwIfSizeNotReached: YR3K_UPLOADER_OPTIONS.throwIfSizeNotReached == 1,
      templatePreview: YR3K_UPLOADER_OPTIONS.templatePreview,
      templateDndArea: YR3K_UPLOADER_OPTIONS.templateDndArea,
    })
  })
})
