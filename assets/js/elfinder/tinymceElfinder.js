/**
 *
 * @see https://github.com/nao-pon/tinymceElfinder
 */

window.tinymceElfinder = function(opts) {
  // elFinder node
  let elfNode = $('<div/>');
  if (opts.nodeId) {
    elfNode.attr('id', opts.nodeId);
    delete opts.nodeId;
  }

  // upload target folder hash
  const uploadTargetHash = opts.uploadTargetHash || 'L1_Lw';
  delete opts.uploadTargetHash;

  // get elFinder instance
  const getfm = open => {
    // CSS class name of TinyMCE conntainer
    const cls = (tinymce.majorVersion < 5)? 'mce-container' : 'tox';
    return new Promise((resolve, reject) => {
      // elFinder instance
      let elf;

      // Execute when the elFinder instance is created
      const done = () => {
        if (open) {
          // request to open folder specify
          if (!Object.keys(elf.files()).length) {
            // when initial request
            elf.one('open', () => {
              // Promise.reject() only keeps its first argument - passing
              // (elf, err) here silently drops err, so bundle both into one
              // rejection value instead.
              elf.file(open)? resolve(elf) : reject({elf, err: 'errFolderNotFound'});
            });
          } else {
            // elFinder has already been initialized
            new Promise((res, rej) => {
              if (elf.file(open)) {
                res();
              } else {
                // To acquire target folder information
                elf.request({cmd: 'parents', target: open}).done(e =>{
                  elf.file(open)? res() : rej();
                }).fail(() => {
                  rej();
                });
              }
            }).then(() => {
              if (elf.cwd().hash == open) {
                resolve(elf);
              } else {
                // Open folder after folder information is acquired
                elf.exec('open', open).done(() => {
                  resolve(elf);
                }).fail(err => {
                  reject({elf, err: err || 'errFolderNotFound'});
                });
              }
            }).catch((err) => {
              reject({elf, err: err || 'errFolderNotFound'});
            });
          }
        } else {
          // show elFinder manager only
          resolve(elf);
        }
      };

      // Check elFinder instance
      if (elf = elfNode.elfinder('instance')) {
        // elFinder instance has already been created
        done();
      } else {
        // To create elFinder instance
        elf = elfNode.dialogelfinder(Object.assign({
          // dialog title
          title : 'File Manager',
          // start folder setting
          startPathHash : open? open : void(0),
          // Set to do not use browser history to un-use location.hash
          useBrowserHistory : false,
          // Disable auto open
          autoOpen : false,
          // elFinder dialog width
          width : '90%',
          // elFinder dialog height
          height : '90%',
          // set getfile command options
          commandsOptions : {
            getfile: {
              oncomplete : 'close'
            }
          },
          bootCallback : (fm) => {
            // set z-index
            fm.getUI().css('z-index', parseInt($('body>.'+cls+':last').css('z-index')) + 100);
          },
          getFileCallback : (files, fm) => {
          }
        }, opts)).elfinder('instance');
        done();
      }
    });
  };

  this.browser = function(callback, value, meta) {
    getfm().then(fm => {
      let cgf = fm.getCommand('getfile');

      const regist = () => {
        fm.options.getFileCallback = cgf.callback = (file, fm) => {
          var url, reg, info;
          url = file.url;

          // todo: This is a hack to remove the phantom path that is injected into the file.url on upload.
          //       Still yet to discover its cause, could be from tinymce?
          // TODO: test in a live site with no basepath
          let remPath = file.baseUrl + file.baseUrl.substring(file.baseUrl.length-2);
          if (url.startsWith(remPath)) {
            url = url.replace(remPath, file.baseUrl);
          }

          // URL normalization
          url = fm.convAbsUrl(url);
          //url = fm.convAbsUrl(file.url);    // use this when/if the above ever gets fixed

          // Make file info
          info = file.name + ' (' + fm.formatSize(file.size) + ')';

          // Provide file and text for the link dialog
          if (meta.filetype == 'file') {
            callback(url, {text: info, title: info});
          }

          // Provide image and alt text for the image dialog
          if (meta.filetype == 'image') {
            callback(url, {alt: info});
          }

          // Provide alternative source and posted for the media dialog
          if (meta.filetype == 'media') {
            callback(url);
          }
        };
        fm.getUI().dialogelfinder('open');
      };
      if (cgf) {
        // elFinder booted
        regist();
      } else {
        // elFinder booting now
        fm.bind('init', () => {
          cgf = fm.getCommand('getfile');
          regist();
        });
      }
    }).catch(({elf, err} = {}) => {
      const msg = elf ? elf.i18n(elf.parseError(err) || 'errOpen') : (err || 'errOpen');
      console.error('elFinder: failed to open file browser:', msg);
    });

    return false;
  };

  // See: https://www.tiny.cloud/docs/tinymce/6/upload-images/#images_upload_handler
  // TinyMCE 6+ requires this handler to return a Promise that resolves with
  // the uploaded file URL (or rejects with an error string). The old
  // (blobInfo, success, failure) callback signature was removed in TINY-8325.
  this.uploadHandler = function (blobInfo, progress) {
    return new Promise(function(resolve, reject) {
      getfm(uploadTargetHash).then((fm) => {
        let fmNode = fm.getUI(),
          file = blobInfo.blob(),
          clipdata = true;
        const err = (e) => {
            var dlg = e.data.dialog || {};
            if (dlg.hasClass('elfinder-dialog-error') || dlg.hasClass('elfinder-confirm-upload')) {
              fmNode.dialogelfinder('open');
              fm.unbind('dialogopened', err);
            }
          },
          closeDlg = () => {
            if (!fm.getUI().find('.elfinder-dialog-error:visible,.elfinder-confirm-upload:visible').length) {
              fmNode.dialogelfinder('close');
            }
          };
        // check file object
        if (file.name) {
          // file blob of client side file object
          clipdata = void(0);
        }
        // Bind err function and exec upload
        // type: 'files' is required so elFinder's own upload.checkFile()
        // treats `files` as real Blob/File objects - without it, elFinder
        // assumes `files[0]` is a pasted HTML/URL string (its own native
        // clipboard-paste path) and calls .replace() on the Blob, which
        // yields zero files and rejects with errUploadNoFiles ("No files
        // found for upload") even though TinyMCE has already shown its own
        // local blob: preview of the image regardless of upload success.
        fm.bind('dialogopened', err).exec('upload', {
          files: [file],
          type: 'files',
          target: uploadTargetHash,
          clipdata: clipdata, // to get unique name on connector
          dropEvt: {altKey: true, ctrlKey: true} // disable watermark on demo site
        }, void(0), uploadTargetHash)
          .done(data => {
            if (data.added && data.added.length) {
              fm.url(data.added[0].hash, { async: true }).done(function(url) {
                // prevent to use browser cache
                url += (url.match(/\?/)? '&' : '?') + '_t=' + data.added[0].ts;
                resolve(fm.convAbsUrl(url));
              }).fail(function() {
                reject(fm.i18n('errFileNotFound'));
              });
            } else {
              reject(fm.i18n(data.error? data.error : 'errUpload'));
            }
          })
          .fail(err => {
            const error = fm.parseError(err);
            reject(fm.i18n(error? (error === 'userabort'? 'errAbort' : error) : 'errUploadNoFiles'));
          })
          .always(() => {
            fm.unbind('dialogopened', err);
            closeDlg();
          });
      }).catch(({elf: fm, err} = {}) => {
        // getfm() may reject before an elFinder instance exists, so fm can
        // be undefined here - fall back to the raw error rather than
        // throwing on fm.parseError()/fm.i18n() of undefined.
        if (!fm) {
          reject(err || 'errUploadNoFiles');
          return;
        }
        const error = fm.parseError(err);
        reject(fm.i18n(error? (error === 'userabort'? 'errAbort' : error) : 'errUploadNoFiles'));
      });
    });
  };
};
