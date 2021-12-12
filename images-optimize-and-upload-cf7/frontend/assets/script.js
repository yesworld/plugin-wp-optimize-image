jQuery(document).ready(function($){

  $.fn.Optimizer3k = function(options) {
    var language = YR3K_UPLOADER_OPTIONS.language;

    var setting = $.extend({
      info_file_origin: language.info_file_origin,
      info_file_compress: language.info_file_compress,
      wrong_format: language.wrong_format,
      info_file_delete: language.info_file_delete,
	  info_file_sizefiles: language.info_file_sizefiles,
	  info_file_uploading: language.info_file_uploading,

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
	  sizefiles: 15,

      templatePreview: '',
      templateDndArea: '',
    }, options)

    var MAXFILE = +this.attr('max-file')
    var NAME_TAG = $(this).data('name')
    var txtErrorMaxFiles = this.attr('max-file-error')
    var txtErrorFormat = setting.wrong_format

    var th = this
    var ID = this.attr('id') ? this.attr('id') : 0

    var countImages = 0;
	var sizef = 0;

    var bodyHTML = '<div class="images-optimize-upload-handler"><div class="images-optimize-upload-container"><div class="images-optimize-upload-inner">' + setting.templateDndArea + '</div></div></div>';
    this.wrapAll('<div class="images-optimize-upload-wrapper"></div>');

    var $dropZone = this.parents('.images-optimize-upload-wrapper');
    var $errorMessage = $('<div class="images-optimize-upload-error" role="alert">').hide();

    var $form = this.parents("form");
    var $btnSubmit = $('input.wpcf7-submit', $form);

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
        .on("dragover dragenter", function(a) {
          $(this).addClass("hover")
        })
        .on("dragleave dragend drop", function(a) {
          $(this).removeClass("hover")
        })
        .on("drop", function(e) {
          upload(e.originalEvent.dataTransfer.files)
        })
      ;

      $dropZone.find('a.images-optimize-upload-button').on("click", function(e) {
        e.preventDefault();
        th.trigger('click')
      });

      // click button to load images
      th.on("change", function(e) {
        upload(this.files)
      });

      // click to remove image
      $list.on('click', 'li del', function(e){
        var $li = $(this).parent();
		
		sizef=sizef - $li.find('.sizefile').text()	
		
        deleteImage($li.find('input[type="hidden"]').val())
        $li.remove();
        countImages--
        errorHandler() //hide error
      });

      // callback success send
      document.addEventListener( 'wpcf7mailsent', function( event ) {
        $list.empty();
        errorHandler() //hide error
        countImages = 0
      }, false );
    }

    /**
     * Uploading images
     * @param images
     */
    function upload(images) {
      if (!images.length) return;

      if (countImages + images.length > MAXFILE){
        errorHandler(txtErrorMaxFiles) //show error
        th.get(0).value = ''
        return;
      }

      // checks files
	 var imm = images;
var imagefiles=[];//картинки закинем в отдельный массив для массовой обработки
		      var formData = new FormData
      // temporarily disabled the form
      disableForm(true);  
      errorHandler() //hide error
	  
          for (var i = 0; imm.length > i; i++) {

			//Загружаем файл в браузер
			var otherfiles=imm[i];
			//захватываем имя, тип, размер
			var namefile = otherfiles.name;
			var formatf=otherfiles.type;
			var sizefile=otherfiles.size / 1024 / 1024;
			var imgf='';

			        // Если форматов нет в списке разрешенных - завершаем выполнение и выводим ошибку
        if (!namefile.match(setting.formatFile)) {
			errorHandler(txtErrorFormat) // show error
			emptyUpload()
			disableForm(false);
			otherfiles=[];//на случай всякий обнулим
          return false;
        }
		
		//если при обработке попалась картинка - закинем ее в отдельный массив для отдельной обработки
		if(formatf.indexOf('image') != -1){

//Картинки обрабатываем отдельно, для этого временно их кидаем в массив и забиваем пока на нее, продорлжаем смотреть следующий элемент
			imagefiles.push(otherfiles);
			continue;

		}else{

		 // Если файл слишком большой - выкидываем ошибку
		if(maxsizeform(sizef,sizefile)==false){return false;}
		sizef = maxsizeform(sizef,sizefile);//если с весом все в порядке - добавляем к итоговому размеру и едем дальше
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
		  createLiHtml('', '', '', randomString, imgf, otherfiles);

		  //добавляем загруженные файлы к форме
		    formData.append('upload-image[]', otherfiles, namefile)
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
	  if (imagefiles.length != 0) {
		  var formData = new FormData				  
      compress
        .compress(imagefiles)
        .then((conversions) => {

          for (var i = 0; conversions.length > i; i++) {

            var photo = conversions[i].photo
            var info = conversions[i].info
			//проверка на размер файла (проходит ли именно сжатый файл нашу проверку)
			if(maxsizeform(sizef,info.endSizeMB)==false){return false;}
			sizef = maxsizeform(sizef,info.endSizeMB);//если все в порядке - складываем и едем дальше

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
	}

//если все файлы благополучно обработались - посчитаем их и запомним
	  countImages += imm.length
	  //очищаем все
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
     * @param sizef
     * @param sizefile
     */
	function maxsizeform(sizef,sizefile){
			//проверка на суммарный размер файлов. Если если превышает заданное значение - вывод ошибки.
//console.log(sizefile);			
		if (sizef + sizefile > setting.sizefiles) {
			errorHandler(setting.info_file_sizefiles + ' ' + setting.sizefiles + 'MB') // show error
			emptyUpload()
			disableForm(false);
          return false;
        } 
		//если по размеру проходим - приплюсовываем его к итогу и его выводим
		return sizef+=sizefile;
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
