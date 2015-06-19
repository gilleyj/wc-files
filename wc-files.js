jQuery(document).ready(function($){
                       
                       // Instantiates the variable that holds the media library frame.
                       var meta_image_frame;
                       
                       // Runs when the image button is clicked.
                       $('#wc_files_attach_button').click(function(e){
                                                     
                                                     // Prevents the default action from occuring.
                                                     e.preventDefault();
                                                     
                                                     // If the frame already exists, re-open it.
                                                     if ( meta_image_frame ) {
                                                     meta_image_frame.open();
                                                     return;
                                                     }
                                                     
                                                     // Sets up the media library frame
                                                     meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
                                                                                                                    title: wc_file_meta_image.title,
                                                                                                                    button: { text:  wc_file_meta_image.button },
                                                                                                                    library: {  }
                                                                                                                    });
                                                     
                                                     // Runs when an image is selected.
                                                     meta_image_frame.on('select', function(){
                                                                         // Grabs the attachment selection and creates a JSON representation of the model.
                                                                         var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
                                                                         
                                                                         console.log(media_attachment);
                                                                         
                                                                         var img_src = $('#wc_files_thumbnail').prop('src');
                                                                         
                                                                         // Sends the attachment URL to our custom image input field.
                                                                         $('#wc_files_attachment_id').val(media_attachment.id);
                                                                         $('#wc_files_mimetype').html('(' + media_attachment.mime + ')');
                                                                         $('#wc_files_filename').html('(' + media_attachment.filename + ')');
                                                                         if (typeof media_attachment.sizes != 'undefined') {
                                                                         if (typeof media_attachment.sizes.thumbnail != 'undefined') {
                                                                         if (typeof media_attachment.sizes.thumbnail.url != 'undefined') {
                                                                         img_src = media_attachment.sizes.thumbnail.url;
                                                                         }}} else if (typeof media_attachment.icon != 'undefined') {
                                                                         img_src = media_attachment.icon;
                                                                         }
                                                                         $('#wc_files_thumbnail').prop('src', img_src);
                                                                         });
                                                     
                                                     // Opens the media library frame.
                                                     meta_image_frame.open();
                                                     });
                       });