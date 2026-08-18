jQuery(document).ready(function ($) {

  $.fn.Optimizer3k = function (options) {
    let language = YR3K_UPLOADER_OPTIONS.language;
    let setting = $.extend({
      info_file_origin: language.info_file_origin,
      info_file_compress: language.info_file_compress,
      wrong_format: language.wrong_format,
      info_file_delete: language.info_file_delete,
      heic_skipped: language.heic_skipped || 'HEIC/HEIF was not added because server-side processing is unavailable.',
      browser_unsupported: language.browser_unsupported || 'This browser cannot prepare compressed files for form submission.',
      preparing: language.preparing || 'Preparing images…',

      formatFile: new RegExp('\\.(' + YR3K_UPLOADER_OPTIONS.formatFile + ')$', 'i'),
      heicFormat: /\.(heic|heif)$/i,
      targetSize: 0.25,
      quality: 0.75,
      minQuality: 0.5,
      qualityStepSize: 0.1,
      maxWidth: 1920,
      maxHeight: 1920,
      resize: true,
      throwIfSizeNotReached: false,
      autoRotate: true,
      heicServerProcessing: false,
      templatePreview: '',
      templateDndArea: '',
    }, options);

    let MAXFILE = +this.attr('max-file');
    let txtErrorMaxFiles = this.attr('max-file-error');
    let th = this;
    let selectedFiles = [];
    let isPreparing = false;

    let bodyHTML = '<div class="images-optimize-upload-handler"><div class="images-optimize-upload-container"><div class="images-optimize-upload-inner">' + setting.templateDndArea + '</div></div></div>';
    this.wrapAll('<div class="images-optimize-upload-wrapper"></div>');

    let $dropZone = this.parents('.images-optimize-upload-wrapper');
    let $errorMessage = $('<div class="images-optimize-upload-error" role="alert">').hide();
    let $form = this.parents('form');
    let $btnSubmit = $('input.wpcf7-submit', $form);

    this.after(bodyHTML);

    let $list = $('<ul class="list"></ul>');
    $dropZone.append($list).append($errorMessage);

    const compress = new Compress(setting);

    initEvents();

    function initEvents() {
      $dropZone.find('.images-optimize-upload-handler')
        .on('drag dragstart dragend dragover dragenter dragleave drop', function (e) {
          e.preventDefault();
          e.stopPropagation();
        })
        .on('dragover dragenter', function () {
          $(this).addClass('hover');
        })
        .on('dragleave dragend drop', function () {
          $(this).removeClass('hover');
        })
        .on('drop', function (e) {
          prepareFilesForSubmission(e.originalEvent.dataTransfer.files);
        });

      $dropZone.find('a.images-optimize-upload-button').on('click', function (e) {
        e.preventDefault();
        th.trigger('click');
      });

      th.on('change', function () {
        prepareFilesForSubmission(this.files);
      });

      $list.on('click', 'li del', function () {
        let key = $(this).parent().attr('data-upload-key');
        selectedFiles = selectedFiles.filter(function (item) {
          return item.key !== key;
        });
        $(this).parent().remove();
        syncInputFiles();
        errorHandler();
      });

      $form.on('submit', function (event) {
        if (isPreparing || !syncInputFiles()) {
          event.preventDefault();
          event.stopImmediatePropagation();
          errorHandler(isPreparing ? setting.preparing : setting.browser_unsupported);
          return false;
        }
      });

      document.addEventListener('wpcf7mailsent', function () {
        resetFiles();
      }, false);
    }

    /**
     * Compress browser-supported images and keep the resulting File objects in
     * the real multipart file input. Nothing is uploaded before CF7 submit.
     */
    function prepareFilesForSubmission(images) {
      if (!images || !images.length) {
        return;
      }

      if (!canPopulateInputFiles()) {
        restoreInputFiles();
        errorHandler(setting.browser_unsupported);
        return;
      }

      let prepared = prepareFiles(images);
      if (!prepared.files.length) {
        restoreInputFiles();
        if (prepared.invalid) {
          errorHandler(setting.wrong_format);
        } else if (prepared.skippedHeic) {
          errorHandler(setting.heic_skipped);
        }
        return;
      }

      if (selectedFiles.length + prepared.files.length > MAXFILE) {
        restoreInputFiles();
        errorHandler(txtErrorMaxFiles);
        return;
      }

      if (prepared.invalid) {
        restoreInputFiles();
        errorHandler(setting.wrong_format);
        return;
      }

      isPreparing = true;
      disableForm(true);
      errorHandler(setting.preparing);

      let clientImages = [];
      let serverImages = [];
      prepared.files.forEach(function (file) {
        if (isServerProcessedHeic(file)) {
          serverImages.push(file);
        } else {
          clientImages.push(file);
        }
      });

      let compressPromise = clientImages.length ? compress.compress(clientImages) : Promise.resolve([]);
      compressPromise
        .then(function (conversions) {
          let additions = [];

          conversions.forEach(function (conversion) {
            let photo = conversion.photo;
            let file = createFile(photo.data, photo.name);
            if (!file) {
              throw new Error(setting.browser_unsupported);
            }

            additions.push({
              key: getRandomString(),
              file: file,
              info: conversion.info,
              preview: URL.createObjectURL(file),
              format: '',
            });
          });

          serverImages.forEach(function (file) {
            additions.push({
              key: getRandomString(),
              file: file,
              info: getOriginalFileInfo(file),
              preview: null,
              format: getHeicFormat(file),
            });
          });

          additions.forEach(function (item) {
            selectedFiles.push(item);
            createLiHtml(item.preview, item.file, item.info, item.key, item.format);
          });

          if (!syncInputFiles()) {
            additions.forEach(function (item) {
              $list.find('li[data-upload-key="' + item.key + '"]').remove();
            });
            selectedFiles = selectedFiles.filter(function (item) {
              return additions.indexOf(item) === -1;
            });
            throw new Error(setting.browser_unsupported);
          }

          errorHandler(prepared.skippedHeic ? setting.heic_skipped : '');
        })
        .catch(function (error) {
          restoreInputFiles();
          errorHandler(error && error.message ? error.message : setting.wrong_format);
        })
        .finally(function () {
          isPreparing = false;
          disableForm(false);
        });
    }

    function prepareFiles(images) {
      let result = [];
      let skippedHeic = false;
      let invalid = false;

      for (let i = 0; i < images.length; i++) {
        let image = images[i];
        if (isHeic(image) && !isServerProcessedHeic(image)) {
          skippedHeic = true;
          continue;
        }

        if (!image.name.match(setting.formatFile) || (!image.type.match('image') && !isServerProcessedHeic(image))) {
          invalid = true;
          continue;
        }

        result.push(image);
      }

      return {
        files: result,
        skippedHeic: skippedHeic,
        invalid: invalid,
      };
    }

    function canPopulateInputFiles() {
      try {
        let transfer = new DataTransfer();
        return !!transfer.items && typeof transfer.items.add === 'function';
      } catch (error) {
        return false;
      }
    }

    function syncInputFiles() {
      if (!canPopulateInputFiles()) {
        return false;
      }

      try {
        let transfer = new DataTransfer();
        selectedFiles.forEach(function (item) {
          transfer.items.add(item.file);
        });
        th.get(0).files = transfer.files;
        return true;
      } catch (error) {
        return false;
      }
    }

    function restoreInputFiles() {
      if (!selectedFiles.length || !syncInputFiles()) {
        th.get(0).value = '';
      }
    }

    function createFile(data, name) {
      try {
        if (data instanceof File) {
          return data;
        }

        return new File([data], name, {
          type: data.type || 'image/jpeg',
          lastModified: Date.now(),
        });
      } catch (error) {
        return null;
      }
    }

    function resetFiles() {
      selectedFiles = [];
      $list.empty();
      th.get(0).value = '';
      errorHandler();
    }

    function errorHandler(text) {
      if (text) {
        $errorMessage.text(text).show();
      } else {
        $errorMessage.empty().hide();
      }
    }

    function getRandomString() {
      return Math.random().toString(36).substr(2, 9);
    }

    function disableForm(disabled) {
      $btnSubmit.prop('disabled', disabled);
      $btnSubmit.toggleClass('disabled', disabled);
    }

    function createLiHtml(objectUrl, photo, info, previewKey, format) {
      let html = getTemplateLi(info, photo.name);
      let $li = $('<li>')
        .attr('data-upload-key', previewKey)
        .html(html);

      if (objectUrl) {
        prependPreview($li, objectUrl);
      } else if (format) {
        prependFormatPreview($li, format);
      }

      $list.append($li);
    }

    function prependPreview($li, src) {
      let previewImg = document.createElement('img');
      Compress.loadImageElement(previewImg, src).then(function () {
        URL.revokeObjectURL(src);
      });

      $li.find('.thumbnail').remove();
      $li.prepend('<div class="thumbnail">' + previewImg.outerHTML + '</div>');
    }

    function prependFormatPreview($li, format) {
      let $thumbnail = $('<div class="thumbnail"></div>');
      $('<div class="heic"></div>').text(format).appendTo($thumbnail);

      $li.find('.thumbnail').remove();
      $li.prepend($thumbnail);
    }

    function getOriginalFileInfo(file) {
      let sizeMB = file.size * 0.000001;
      return {
        startSizeMB: sizeMB,
        endSizeMB: sizeMB,
        startWidth: 0,
        startHeight: 0,
        endWidth: 0,
        endHeight: 0,
      };
    }

    function getTemplateLi(info, photoName) {
      return setting.templatePreview
        .replace('{{photoName}}', escapeHtml(photoName))
        .replace('{{txtInfoFileOrigin}}', escapeHtml(setting.info_file_origin))
        .replace('{{beforeSize}}', Number(info.startSizeMB).toFixed(2))
        .replace('{{startWidth}}', Number(info.startWidth).toFixed())
        .replace('{{startHeight}}', Number(info.startHeight).toFixed())
        .replace('{{txtInfoFileCompress}}', escapeHtml(setting.info_file_compress))
        .replace('{{afterSize}}', Number(info.endSizeMB).toFixed(2))
        .replace('{{endWidth}}', Number(info.endWidth).toFixed())
        .replace('{{endHeight}}', Number(info.endHeight).toFixed())
        .replace('{{txtDelete}}', escapeHtml(setting.info_file_delete));
    }

    function escapeHtml(value) {
      return $('<div>').text(String(value)).html();
    }

    function isHeic(file) {
      return file.name.match(setting.heicFormat);
    }

    function getHeicFormat(file) {
      let match = file.name.match(setting.heicFormat);
      return match ? match[1].toUpperCase() : 'HEIC';
    }

    function isServerProcessedHeic(file) {
      return setting.heicServerProcessing && isHeic(file);
    }
  };
});
