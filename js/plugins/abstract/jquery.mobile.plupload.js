define([
	'config',
	'jquery',
	'classes/Utils',
    'classes/I18n'
], function(app, $, Utils, I18n) {
	$(function() {
		var plupload_icons = {
			file: 'octicon-file-text',
			avi: 'octicon-file-media',
			mp4: 'octicon-file-media',
			mov: 'octicon-file-media',
			pdf: 'octicon-file-pdf',
			wmv: 'octicon-file-media',
			zip: 'octicon-file-zip'
		};
		var uploaders = [];
		
		var upload_init = function(selector) {
			var $pl_cnt = $.type(selector) === 'string' ? $(selector) : selector;
			var $pl_add_btn = $('.plupload-add-btn', $pl_cnt);
			var $pl_upload_btn = $('.plupload-upload-btn', $pl_cnt);
			var $pl_upload_list = $('.plupload-list', $pl_cnt);
			var $pl_queue_list = $('.plupload-queue', $pl_cnt);
			var $pl_input = $('.plupload-hidden', $pl_cnt);
			var upload_dir = $pl_cnt.attr('data-upload-dir');
			var module_name = $pl_cnt.attr('data-module');
			var field_name = $pl_cnt.attr('data-field');
			var extensions = $pl_cnt.attr('data-file-ext');
			var config_name = $pl_cnt.attr('data-cfg');
			var pk = $pl_cnt.attr('data-pk');
			var is_read_only = parseInt( $pl_cnt.attr('data-readonly') ) === 1;
			var is_image = parseInt( $pl_cnt.attr('data-is-image') ) === 1;
			var is_multi = parseInt( $pl_cnt.attr('data-is-multi') ) === 1;
			var max_uploads = parseInt( $pl_cnt.attr('data-max-files') );
            var max_size = $pl_cnt.attr('data-max-size');
			var num_uploaded = 0;
			var ext_title = is_image ? I18n.t('upload.allow.image') : I18n.t('upload.allow.file');
			
			//set AJAX URLs
			var plupload_url = app.adminFileUploadURL + '/' + module_name + '/' + config_name + '/' + (is_image ? 'i' : 'f');
            plupload_url += pk.length ? '/' + pk : '';
			var plupload_delete_url = app.adminFileDeleteURL + '/' + module_name;
			
			if (max_uploads <= 0) {
				max_uploads = 1;
				is_multi = false;
			}
			
			//set array of already uploaded files, if set
			var uploaded_files = [];
			var files = $pl_cnt.attr('data-files');
			if (files !== undefined && files.length) {
				uploaded_files = JSON.parse(files);
			}
			if ( $.isPlainObject(uploaded_files) ) {
				uploaded_files = [uploaded_files];
			} else if ( ! $.isArray(uploaded_files) ) {
				uploaded_files = [];
			}

			//get form submit button from DOM, to disable on upload
			var submit_btn_sel = $pl_cnt.attr('data-form-submit');
			if ( ! submit_btn_sel) {
				submit_btn_sel = 'input[type="submit"],button[type="submit"]';
			}
			var $form_submit_btn = $(submit_btn_sel);
			
			var upload_clear = function(uploader) {
				$pl_cnt.attr('data-files', '[]');
				$pl_upload_list.empty();
				$pl_queue_list.empty();
				$pl_cnt.children('.plupload-uploaded-list').hide();
				$('input[name="' + field_name + '"]', $pl_input).each(function() {
					$(this).remove();
				});
				$('<input/>')
					.attr('type', 'hidden')
					.attr('name', field_name)
					.val('')
				.appendTo($pl_input);
				
				for (var i=0; i < uploader.files.length; i++) {
					uploader.removeFile(uploader.files[i]);
				}
				
				var index = false;
				for (i=0; i < uploaders.length; i++) {
					if (uploaders[i].id === uploader.id) {
						uploaders[i].destroy();
						index = i;
						break;
					}
				}
				if (index) {
					uploaders.splice(index, 1);
				}
				
				$pl_add_btn.attr('disabled', false);
				$pl_cnt.off('upload:clear');
			};
			
			var uploaded_list_item = function(uploader, data, silent) {
				num_uploaded++;
				var filetype = data.filename.substr( data.filename.lastIndexOf('.') + 1 ).toLowerCase();
				var filepath = window.location.protocol + '//' + window.location.host + upload_dir + '/' + data.filename;
				var file_id = (is_image ? 'img' : 'file') + '-' + data.filename.replace('.', '-');
				var $li = $('<li/>').addClass('ui-li-has-thumb');
				var $a = $('<a/>')
					.attr('href', filepath)
					.attr('target', '_blank');
				if (is_image) {
					$('<img/>').attr('src', filepath).attr('id', file_id).appendTo($a);
				} else {
					var icon = plupload_icons['file'];
					if (filetype.lastIndexOf('pdf') > -1) {
						icon = plupload_icons['pdf'];
					} else if (filetype.lastIndexOf('zip') > -1) {
						icon = plupload_icons['zip'];
					} else if (plupload_icons[filetype]) {
						icon = plupload_icons[filetype];
					}
					$('<span/>')
						.attr('id', file_id)
						.addClass('plupload-file-icon mega-octicon more-mega ' + icon)
					.appendTo($a);
				}
				$('<h3/>').text(data.filename).appendTo($a);
				$('<p/>')
					.html('<strong>' + data.filetype + '<strong> ' + data.filesize)
				.appendTo($a);
				$a.appendTo($li);

				if (is_read_only) {
					$a.addClass('ui-disabled');
				} else {
					$('<a/>')
						.attr('href', '#')
						.click(function (e) {
							var $a = $(this);
							var message = I18n.t('delete') + ' ' + data.filename + '?';
							Utils.showModalConfirm( I18n.t('confirm'), message, function () {
								$.ajax({
									url: plupload_delete_url,
									type: 'DELETE',
									dataType: 'json',
									data: {
										file: data.filename,
										cfg: config_name,
										img: (is_image ? 1 : 0)
									},
								}).done(function (status) {
									//NOTE: deleting the image from the form field
									//list is done here despite errors that occur
									//while deleting, otherwise have to delete
									//files from db directly; error message will
									//still be shown
									var count = 0;
									var num_fields = 0;
									$('input[name="' + field_name + '"]', $pl_input).each(function () {
										num_fields++;
									});
									$('input[name="' + field_name + '"]', $pl_input).each(function () {
										var $input = $(this);
										if ($input.val() === data.filename) {
											if (num_fields === 1) {
												$input.val('');
											} else {
												$input.remove();
											}
										} else {
											count++;
										}
									});
									if (count === 0) {
										$pl_upload_list.parent('.plupload-uploaded-list').slideUp();
									}

									//delete file from uploads fileinfo
									var uf = [];
									for (var i = 0; i < uploaded_files.length; i++) {
										var info = uploaded_files[i];
										if (info.filename !== data.filename) {
											uf.push(info);
										}
									}
									uploaded_files = uf;
									$pl_cnt.attr('data-files', JSON.stringify(uploaded_files));

									$a.parent('li').remove();
									uploader.disableBrowse(false);
									$pl_add_btn.attr('disabled', false);
									num_uploaded--;

									err_message = '';
									if (status.OK && status.OK == 1) {
										return false;
									} else if (status.errors) {
										err_message = status.errors.join("<br/>");
									} else {
                                        err_message = I18n.t('error.delete', data.filename);
									}

									Utils.showModalWarning( I18n.t('error'), err_message);
								}).fail(function (jqXHR) {
                                    var resp = Utils.parseJqXHR(jqXHR);
                                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                                    if (error.length === 0) {
                                        error = I18n.t('error.delete', data.filename);
                                    }
                                    Utils.showModalWarning( I18n.t('error'), error);
								});
							});
					}).appendTo($li);
				}
				$li.appendTo($pl_upload_list);
				$pl_upload_list.listview('refresh');

				if (num_uploaded === 1) {
					//add filename to placeholder input field
					$('input[name="' + field_name + '"]', $pl_input).each(function() {
						var $input = $(this);
						if ( $input.val().length === 0) {
							$input.val(data.filename);
							if (silent === false) {
								$input.trigger('change');
							}
							return false;
						}
					});
					$pl_cnt.children('.plupload-uploaded-list').slideDown();
				} else {
				//add hidden input with filename
					var $input = $('<input/>')
						.attr('type', 'hidden')
						.attr('name', field_name)
						.val(data.filename)
					.appendTo($pl_input);
					if (silent === false) {
						$input.trigger('change');
					}
				}
		
				if (num_uploaded === max_uploads) {
					uploader.disableBrowse(true);
					$pl_add_btn.attr('disabled', true);
				}
			};
			
			var uploader = new plupload.Uploader({
				runtimes : 			is_multi ? 'html5,html4' : 'html4',
				url : 				plupload_url,
				file_data_name : 	'file',
				browse_button : 	$pl_add_btn.get(0),
				container : 		$pl_cnt.get(0),
				unique_names:		false,
				filters : {
					max_file_size : max_size,
					mime_types: [
						{title : ext_title, extensions : extensions}
					],
					prevent_duplicates:	true
				},
				init: {
					Init: function (uploader) {
						if (uploaded_files.length) {
							for (var i=0; i < uploaded_files.length; i++) {
								uploaded_list_item(uploader, uploaded_files[i], true);
							}
						}
                        if (is_read_only) {
                            uploader.disableBrowse(true);
                            $pl_add_btn.attr('disabled', true);
                        }
					},
					BeforeUpload: function(uploader) {
						$pl_cnt.children('.plupload-status').slideDown();
						$form_submit_btn.attr('disabled', true);
					},
					PostInit: function(uploader) {
						$pl_upload_btn.on('click', function() {
						    if (is_read_only) {
						        return false;
                            }
							uploader.start();
							return false;
						});
					},
					FilesAdded: function(uploader, files) {
						var num_added = files.length;
						var num_queued = uploader.files.length - num_added;

						if ( (num_uploaded + num_queued + num_added) > max_uploads) {
							var num_removed = num_uploaded + num_queued + num_added - max_uploads;
							var args = [
                                max_uploads,
                                num_removed === 1 ? I18n.t('file.has') : I18n.t('files.have', num_removed)
                            ];
                            var message = I18n.t('message.upload.max', args);
							Utils.showModalDialog( I18n.t('message'), message);
							for (var i=num_added-1; i >= (num_added - num_removed); i--) {
								uploader.removeFile(files[i]);
							}
							uploader.disableBrowse(true);
							$pl_add_btn.attr('disabled', true);
							num_added = uploader.files.length - num_queued;
						} else if (num_added === max_uploads) {
							uploader.disableBrowse(true);
							$pl_add_btn.attr('disabled', true);
						}
						for (var i=0; i < num_added; i++) {
							var File = files[i];
							var $li = $('<li/>');
							var $a = $('<a/>').attr('href', '#').click(function() {});
							$('<h3/>').text(File.name).appendTo($a);
							$a.appendTo($li);
							(function(uploader, File) {		
								$('<a/>')
									.attr('href', '#')
									.click(function() {
										var $a = $(this);
                                        var message = I18n.t('message.remove', File.name);
										Utils.showModalConfirm( I18n.t('confirm'), message, function() {
											uploader.removeFile(File);
											$a.parent('li').remove();
											if (uploader.files.length === 0) {
												$pl_queue_list.parent('.plupload-queued-list').slideUp();
												$pl_upload_btn.attr('disabled', true);
											}
												
											uploader.disableBrowse(false);
											$pl_add_btn.attr('disabled', false);
										});	
									}).appendTo($li);							
							})(uploader, File);
							$li.appendTo($pl_queue_list);
							$pl_queue_list.listview('refresh');
						}
						
						$pl_cnt.children('.plupload-queued-list').slideDown();
						$pl_upload_btn.attr('disabled', false);
					},
					FileUploaded: function(uploader, file, resp) {
						var response = JSON.parse(resp.response);
						var err_message = '';
				
						if (response.errors && response.errors.length > 0) {
						//error response from server
							err_message = response.errors.join("<br/>");
						} else if (resp.status !== 200) {
						//file not uploaded to server
                            err_message = I18n.t('error.upload', file.name);
						} else {
						//file uploaded succesfully
							if (response.errors) {
								delete response.errors;
							}
							uploaded_list_item(uploader, response, false);
							uploaded_files.push(response);
							$pl_cnt.attr('data-files', JSON.stringify(uploaded_files) );
							
						}
						
						//remove file from queue
						uploader.removeFile(file);
						
						$pl_upload_btn.attr('disabled', true);
						if (err_message.length) {
							Utils.showModalWarning( I18n.t('error'), err_message);
							uploader.disableBrowse(false);
							$pl_add_btn.attr('disabled', false);
						}
					},
					UploadComplete: function(uploader, files) {
						$pl_cnt.children('.plupload-queued-list,.plupload-status').slideUp();
						$pl_queue_list.empty();
								
						//keep form submit button disabled until pending queues finish uploading
						var has_uploads = false;
						for (var i=0; i < uploaders.length; i++) {
							if (uploaders[i].STARTED) {
								has_uploads = true;
								break;
							}
						}
						if ( ! has_uploads) {
							$form_submit_btn.attr('disabled', false);
						}
					},
					UploadProgress: function(uploader, file) {
						$pl_cnt.find('.plupload-status > span').css('width', file.percent + '%');
					},
					Error: function(uploader, error) {
						var message = error.message;
						if (error.code === plupload.FILE_EXTENSION_ERROR) {
							message = I18n.t('error.upload.invalid');
						} else if (error.code === plupload.FILE_SIZE_ERROR) {
							message = I18n.t('error.upload.maxsize');
						} else if (error.code === plupload.FILE_DUPLICATE_ERROR) {
							message = I18n.t('error.upload.dupe');
						}
						
						message += ": " + error.file.name;
						Utils.showModalWarning( I18n.t('error'), message);
					}
				}
			});

            uploader.init();
			uploaders.push(uploader);
			
			$pl_cnt.on('upload:clear', function(e) {
				e.stopPropagation();
				upload_clear(uploader);
			});
		};
		
		$('body').on('upload:reset', '.plupload', function(e) {
			e.stopPropagation();
			upload_init( $(this) );
		});

        $('.plupload').each(function() {
            upload_init( $(this) );
        });
		
		$(window).on('page:unload', function() {
            $('.plupload').each(function() {
                var $pl_cnt = $(this);
                $pl_cnt.off('upload:clear');
                $('.plupload-upload-btn', $pl_cnt).off('click');
                $('.plupload-list', $pl_cnt).empty();
                $('.plupload-queue', $pl_cnt).empty();
            });
			for (var i=0; i < uploaders.length; i++) {
				uploaders[i].destroy();
			}
			uploaders = null;
            $('body').off('upload:reset', '.plupload');
			$(this).off('page:unload');
		});

	});
});