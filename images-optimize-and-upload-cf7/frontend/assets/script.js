jQuery(document).ready(function($){

  $.fn.Optimizer3k = function(options) {
    var setting = $.extend({
      info_file_origin: YR3K_UPLOADER_OPTIONS.language.info_file_origin,
      info_file_compress: YR3K_UPLOADER_OPTIONS.language.info_file_compress,
      info_file_wrong_format: YR3K_UPLOADER_OPTIONS.language.info_file_wrong_format,
      info_file_delete: YR3K_UPLOADER_OPTIONS.language.info_file_delete,
	  info_file_limit_files_size: YR3K_UPLOADER_OPTIONS.language.info_file_limit_files_size,
	  info_file_uploading: YR3K_UPLOADER_OPTIONS.language.info_file_uploading,

      ajax_url: YR3K_UPLOADER_OPTIONS.ajax_url,
      formatFile: new RegExp('\.('+ YR3K_UPLOADER_OPTIONS.formatFile +')$', 'i'),

      targetSize: 0.25,
      quality: 0.75,
      minQuality: 0.5,
      qualityStepSize: 0.1,
      maxWidth: 1920,
      maxHeight: 1920,
      resize: true,
      throwIfSizeNotReached: false,
      autoRotate: true,
	  limitFilesSize: 15,

      templatePreview: '',
      templateDndArea: '',
    }, options)

    const _THIS = this
    const LIMIT_MAX_FILES = +this.attr('max-file')
    const NAME_TAG = $(this).data('name')
    const txtErrorMaxFiles = this.attr('max-file-error')
    const txtErrorFormat = setting.info_file_wrong_format

    var ID = this.attr('id') ? this.attr('id') : 0

    var countImages = 0;
	var currentFileSize = 0;

    this.wrapAll('<div class="images-optimize-upload-wrapper"></div>');

    var $dropZone = this.parents('.images-optimize-upload-wrapper');
    var $errorMessage = $('<div class="images-optimize-upload-error" role="alert">').hide();

    var $form = this.parents("form");
    var $btnSubmit = $('input.wpcf7-submit', $form);

    var bodyHTML = '<div class="images-optimize-upload-handler"><div class="images-optimize-upload-container"><div class="images-optimize-upload-inner">' + setting.templateDndArea + '</div></div></div>';
    this.after(bodyHTML);

    var $list = $('<ul class="list"></ul>');
    $dropZone
      .append($list)
      .append($errorMessage);

    const compress = new Compress(setting)

    /* Initialize Events */
    initEvents();
    function initEvents() {
      $dropZone.find('.images-optimize-upload-handler')
        .on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
          e.preventDefault();
          e.stopPropagation();
        })
        .on("dragover dragenter", function() {
          $(this).addClass("hover")
        })
        .on("dragleave dragend drop", function() {
          $(this).removeClass("hover")
        })
        .on("drop", function(e) {
          callbackUploadFiles(e.originalEvent.dataTransfer.files)
        })
      ;

      $dropZone.find('a.images-optimize-upload-button').on("click", function(e) {
        e.preventDefault();
        _THIS.trigger('click')
      });

      // click button to load images
      _THIS.on("change", function() {
        callbackUploadFiles(this.files)
      });

      // click to remove image
      $list.on('click', 'li del', function(e){
        var $li = $(this).parent();

		currentFileSize=currentFileSize - $li.find('.sizefile').text()

        deleteImage($li.find('input[type="hidden"]').val())

        $li.remove();
        countImages--
        errorHandler() //hide error
      });

      // callback success send
      document.addEventListener( 'wpcf7mailsent', function() {
        $list.empty();
        errorHandler() //hide error
        countImages = 0
      }, false );
    }

    /**
     * Uploading files
     */
    async function callbackUploadFiles(files) {
      if (files.length === 0) return;

      if (countImages + files.length > LIMIT_MAX_FILES){
        errorHandler(txtErrorMaxFiles) //show error
        _THIS.get(0).value = ''
        return;
      }

      // checks files
      var imagefiles=[];//картинки закинем в отдельный массив для массовой обработки
      // temporarily disabled the form
      disableForm(true);
      errorHandler() //hide error

      for (let i = 0; files.length > i; i++) {
        const file = files[i];

        var namefile = file.name;
        var formatf=file.type;
        var sizefile=file.size / 1024 / 1024;
        var imgf='';

        // Если форматов нет в списке разрешенных - завершаем выполнение и выводим ошибку
        if (!namefile.match(setting.formatFile)) {
			errorHandler(txtErrorFormat) // show error
			emptyUpload()
			disableForm(false);
            return false;
        }

		if(formatf.indexOf('image') != -1){
            let reds = await compressImage([file])
            debugger
            //Картинки обрабатываем отдельно, для этого временно их кидаем в массив и забиваем пока на нее, продорлжаем смотреть следующий элемент
			imagefiles.push(file);
			continue;

		}else{
            // Если файл слишком большой - выкидываем ошибку
            if(maxsizeform(currentFileSize,sizefile)==false){return false;}

            currentFileSize = maxsizeform(currentFileSize,sizefile);//если с весом все в порядке - добавляем к итоговому размеру и едем дальше
            //рандомная строка, чтобы не было путаницы с файлами
			var randomString = getRandomString();
            //сопоставляем тип файла с логотипом
            switch (true) {
                // если аудио
                case formatf.indexOf('audio') != -1:
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/audio.svg">';
                    break;
                // если видео
                case formatf.indexOf('video') != -1:
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/video.svg">';
                    break;
                // если word
                case ((formatf.indexOf('wordprocessingml') != -1) || (formatf.indexOf('msword') != -1)):
            imgf = '<img width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/word.svg">';
                   break;
               // если excel
               case ((formatf.indexOf('spreadsheetml') != -1) || (formatf.indexOf('excel') != -1)):
            imgf = '<img style="padding:10px;" width="160" height="160" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/excel.svg">';
                   break;
               // если powerpoint
               case ((formatf.indexOf('presentation') != -1) || (formatf.indexOf('powerpoint') != -1)):
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/powerpoint.svg">';
                   break;
               // если pdf
               case formatf.indexOf('pdf') != -1:
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/pdf.svg">';
                   break;
               // если архив
               case formatf.indexOf('compressed') != -1:
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/archive.svg">';
                   break;
               // иначе, выводим общую картинку
               default:
            imgf = '<img style="padding:10px;" width="128" height="128" alt="" src="/wp-content/plugins/images-optimize-and-upload-cf7/frontend/assets/logo/text.svg">';
                    break;
            }

            //формируем предпросмотр
            createLiHtml('', '', '', randomString, imgf, file);

            //добавляем загруженные файлы к форме
		    formData.append('upload-image[]', file, namefile)
            formData.append('upload-image-key[]', randomString)
          }
	  }

	  //отправляем только прочие файлы...
      formData.append("action", "yr_api_uploader");
      formData.append("id", ID);
      send(formData)

      //обработаем отдельно в новой форме картинки
      iii(imagefiles);

      function iii(imagefiles){

          if (imagefiles.length == 0) {
              return
          }
      const formData = new FormData
      compress
        .compress(imagefiles)
        .then((conversions) => {

          for (var i = 0; conversions.length > i; i++) {

            var photo = conversions[i].photo
            var info = conversions[i].info
			//проверка на размер файла (проходит ли именно сжатый файл нашу проверку)
			if(maxsizeform(currentFileSize,info.endSizeMB)==false){return false;}
			currentFileSize = maxsizeform(currentFileSize,info.endSizeMB);//если все в порядке - складываем и едем дальше

            // Create an object URL which points to the photo Blob data
            const objectUrl = URL.createObjectURL(photo.data)

            var randomString = getRandomString();

            // create Li and append to list UL
            createLiHtml(objectUrl, photo, info, randomString,'','');
            formData.append('upload-image[]', photo.data, photo.name)
            formData.append('upload-image-key[]', randomString)
          }
//отправляем картинки
          formData.append("action", "yr_api_uploader");
          formData.append("id", ID);
          send(formData)
      })
	}

    //если все файлы благополучно обработались - посчитаем их и запомним
	  countImages += files.length
	  //очищаем все
		emptyUpload()

    }

    /**
     * @param images[]
     * @return Promise<any[]>
     */
    async function compressImage(images) {
      return new Promise((resolve) => {
        compress
          .compress(images)
          .then((conversions) => {

            const result = []
            for (let i = 0; conversions.length > i; i++) {

              let image = conversions[i].photo
              image.info = conversions[i].info
              image.id = getRandomString()

              // Create an object URL which points to the photo Blob data
              image.preview = URL.createObjectURL(image.data)
              result.push(image)
            }

            resolve(result)
          })
      })
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
      _THIS.empty()
      _THIS.get(0).value = ''
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
      errorHandler(setting.info_file_uploading) //загрузка файла
      $.ajax({
        url: setting.ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(res) {
          disableForm(false);

          if (!res.success) {
            return;
          }
          console.log(res);

          for (var i=0; i < res.data.length; i++) {
            var key = res.data[i].key;
            var value = res.data[i].temp + '/' + res.data[i].value;
            $list.find('li.yr3k-' + key)
              .append('<input type="hidden" name="' + NAME_TAG + '[]" value="' + value + '">')
              .show();
          }
          errorHandler() //error
        },
        error: function() {
          disableForm(false);
          console.log('error: ', arguments)
        }
      })
    }


    /**
     * Files size
     * @param currentFileSize
     * @param sizefile
     */
	function maxsizeform(currentFileSize,sizefile){
			//проверка на суммарный размер файлов. Если если превышает заданное значение - вывод ошибки.
//console.log(sizefile);
		if (currentFileSize + sizefile > setting.limitFilesSize) {
			errorHandler(setting.info_file_limit_files_size + ' ' + setting.limitFilesSize + 'MB') // show error
			emptyUpload()
			disableForm(false);
          return false;
        }
		//если по размеру проходим - приплюсовываем его к итогу и его выводим
		return currentFileSize+=sizefile;
  }


    /**
     * Delete an image
     * @param file
     */
    function deleteImage(file) {
      var data = {
        action: 'yr_api_delete',
        file: file
      }

      $.ajax({
        url: setting.ajax_url,
        type: 'post',
        data: data,
        dataType: 'json',
        cache: false,
        success: function(res) {
          console.log('success: ',res)

          if (!res.success) {
            return;
          }
        },
        error: function() {
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
    function createLiHtml(objectUrl, photo, info, randomClassName, imgf, otherfiles) {
		var fsize='';
		var html='';
		var primg='';
		//если перед нами картинка - создаем превью, делаем замены в макете. Иначе - создаем свой макет
		if (imgf===''){
      var previewImg = document.createElement('img')

      // Set the preview img src to the object URL and wait for it to load
      Compress
        .loadImageElement(previewImg, objectUrl)
        .then(() => {
          // Revoke the object URL to free up memory
          URL.revokeObjectURL(objectUrl)
        })

      html = getTemplateLi(info, photo.name);
	  fsize = info.endSizeMB;
	  primg = previewImg.outerHTML;
		}else{
			fsize = otherfiles.size / 1024 / 1024;
                //Создаем макет загруженного файла: добавляем название, формат, размер и картинку загружаемого файла
			html = '<span>'+otherfiles.name+'</span><span>'+otherfiles.type+'</span><span>'+fsize.toFixed(2)+'Mb</span><del data-note="'+setting.info_file_delete+'">×</del>';
			primg=imgf;
		}

      var $li = $('<li>')
        .hide()
        .addClass('yr3k-' + randomClassName)
        .html(html+'<i style="display:none" class="sizefile">'+fsize+'</i>')
        .prepend('<div class="thumbnail">' + primg + '</div>')

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

  }
});
