jQuery(document).ready(function ($) {

  $.fn.Optimizer3k = function (options) {
    let language = YR3K_UPLOADER_OPTIONS.language;

    let setting = $.extend({
      info_file_origin: language.info_file_origin,
      info_file_compress: language.info_file_compress,
      wrong_format: language.wrong_format,
      info_file_delete: language.info_file_delete,

      ajax_url: YR3K_UPLOADER_OPTIONS.ajax_url,
      formatFile: new RegExp('\(' + YR3K_UPLOADER_OPTIONS.formatFile + ')$', 'i'),

      targetSize: 0.25,
      quality: 0.75,
      minQuality: 0.5,
      qualityStepSize: 0.1,
      maxWidth: 1920,
      maxHeight: 1920,
      resize: true,
      throwIfSizeNotReached: false,
      autoRotate: true,

      templatePreview: '',
      templateDndArea: '',
    }, options)

    let MAXFILE = +this.attr('max-file')
    let NAME_TAG = $(this).data('name')
    let txtErrorMaxFiles = this.attr('max-file-error')
    let txtErrorFormat = setting.wrong_format

    let th = this
    let ID = this.attr('id') ? this.attr('id') : 0

    let countImages = 0;

    let bodyHTML = '<div class="images-optimize-upload-handler"><div class="images-optimize-upload-container"><div class="images-optimize-upload-inner">' + setting.templateDndArea + '</div></div></div>';
    this.wrapAll('<div class="images-optimize-upload-wrapper"></div>');

    let $dropZone = this.parents('.images-optimize-upload-wrapper');
    let $errorMessage = $('<div class="images-optimize-upload-error" role="alert">').hide();

    let $form = this.parents("form");
    let $btnSubmit = $('input.wpcf7-submit', $form);

    this.after(bodyHTML);

    let $list = $('<ul class="list"></ul>');
    $dropZone
      .append($list)
      .append($errorMessage);

    const compress = new Compress(setting)

    /* Initialize Events */
    initEvents();

    function initEvents() {
      $dropZone.find('.images-optimize-upload-handler')
        .on("drag dragstart dragend dragover dragenter dragleave drop", function (e) {
          e.preventDefault();
          e.stopPropagation();
        })
        .on("dragover dragenter", function () {
          $(this).addClass("hover")
        })
        .on("dragleave dragend drop", function () {
          $(this).removeClass("hover")
        })
        .on("drop", function (e) {
          upload(e.originalEvent.dataTransfer.files)
        })
      ;

      $dropZone.find('a.images-optimize-upload-button').on("click", function (e) {
        e.preventDefault();
        th.trigger('click')
      });

      // click button to load images
      th.on("change", function (e) {
        upload(this.files)
      });

      // click to remove image
      $list.on('click', 'li del', function () {
        let $li = $(this).parent();
        deleteImage($li.find('input[type="hidden"]').val())

        $li.remove();
        countImages--
        errorHandler() //hide error
      });

      // callback success send
      document.addEventListener('wpcf7mailsent', function () {
        $list.empty();
        errorHandler() //hide error
        countImages = 0
      }, false);
    }

    /**
     * Uploading images
     * @param images
     */
    function upload(images) {
      if (!images.length) return;

      if (countImages + images.length > MAXFILE) {
        errorHandler(txtErrorMaxFiles) //show error
        th.get(0).value = ''
        return;
      }

      // checks files
      images = prepareFiles(images);
      if (!images) {
        emptyUpload()
        errorHandler(txtErrorFormat) // show error
        return;
      }

      // temporarily disabled the form
      disableForm(true);

      countImages += images.length
      errorHandler() //hide error

      const formData = new FormData
      compress
        .compress(images)
        .then((conversions) => {

          for (let i = 0; conversions.length > i; i++) {

            let photo = conversions[i].photo
            let info = conversions[i].info

            // Create an object URL which points to the photo Blob data
            const objectUrl = URL.createObjectURL(photo.data)

            let randomString = getRandomString();

            // create Li and append to list UL
            createLiHtml(objectUrl, photo, info, randomString);
            formData.append('upload-image[]', photo.data, photo.name)
            formData.append('upload-image-key[]', randomString)
          }

          formData.append("action", "yr_api_uploader");
          formData.append("id", ID);
          send(formData)
        })

      emptyUpload()
    }

    /**
     * Display an error
     * @param txt
     */
    function errorHandler(txt) {
      if (txt) {
        $errorMessage.html(txt).show()
      } else {
        $errorMessage.empty().hide()
      }
    }

    /**
     * Clear images from form
     */
    function emptyUpload() {
      th.empty()
      th.get(0).value = ''
    }

    /**
     * Generate a random string
     * @return {string}
     */
    function getRandomString() {
      return Math.random().toString(36).substr(2, 9)
    }

    /**
     * Temporarily disable the submit button of the form
     * @param disabled
     */
    function disableForm(disabled) {
      $btnSubmit.prop('disabled', disabled);
      if (disabled) {
        $btnSubmit.addClass('disabled')
      } else {
        $btnSubmit.removeClass('disabled')
      }
    }

    /**
     * Send images into the Server
     * @param data
     */
    function send(data) {
      $.ajax({
        url: setting.ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function (res) {
          disableForm(false);

          if (!res.success) {
            return;
          }

          for (let i = 0; i < res.data.length; i++) {
            let key = res.data[i].key;
            let value = res.data[i].temp + '/' + res.data[i].value;
            $list.find('li.yr3k-' + key)
              .append('<input type="hidden" name="' + NAME_TAG + '[]" value="' + value + '">')
              .show();
          }
        },
        error: function () {
          disableForm(false);
          console.log('error: ', arguments)
        }
      })
    }

    /**
     * Delete an image
     * @param file
     */
    function deleteImage(file) {
      let data = {
        action: 'yr_api_delete',
        file: file
      }

      $.ajax({
        url: setting.ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        cache: false,
        success: function (res) {
          console.log('success: ', res)

          if (!res.success) {
            return;
          }
        },
        error: function () {
          console.log('error: ', arguments)
        }
      })
    }

    /**
     * Create images preview
     * @param objectUrl
     * @param photo
     * @param info
     * @param randomClassName
     */
    function createLiHtml(objectUrl, photo, info, randomClassName) {
      let previewImg = document.createElement('img')

      // Set the preview img src to the object URL and wait for it to load
      Compress
        .loadImageElement(previewImg, objectUrl)
        .then(() => {
          // Revoke the object URL to free up memory
          URL.revokeObjectURL(objectUrl)
        })

      let html = getTemplateLi(info, photo.name);

      let $li = $('<li>')
        .hide()
        .addClass('yr3k-' + randomClassName)
        .html(html)
        .prepend('<div class="thumbnail">' + previewImg.outerHTML + '</div>')

      $list.append($li)
    }

    function getTemplateLi(info, photoName) {
      return setting.templatePreview
        .replace("{{photoName}}", photoName)
        .replace("{{txtInfoFileOrigin}}", setting.info_file_origin)
        .replace("{{beforeSize}}", info.startSizeMB.toFixed(2))
        .replace("{{startWidth}}", info.startWidth.toFixed())
        .replace("{{startHeight}}", info.startHeight.toFixed())
        .replace("{{txtInfoFileCompress}}", setting.info_file_compress)
        .replace("{{afterSize}}", info.endSizeMB.toFixed(2))
        .replace("{{endWidth}}", info.endWidth.toFixed())
        .replace("{{endHeight}}", info.endHeight.toFixed())
        .replace("{{txtDelete}}", setting.info_file_delete)
    }

    /**
     * Check file extension
     *
     * @param images
     * @return {Array}
     */
    function prepareFiles(images) {
      let result = []
      let allowFormat = setting.formatFile;

      for (let i = 0, img; img = images[i]; i++) {

        // Only process image files.
        if (!img.type.match('image') || !img.name.match(allowFormat)) {
          return false;
        }

        result.push(img)
      }
      return result;
    }
  }
})
