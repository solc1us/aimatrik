<script type="text/javascript" src="{{ asset('assets/extensions/ckeditor/ckeditor.js') }}"></script>
<script>
    $('.editor-textarea').each(function(e) {
        CKEDITOR.replace(this.id, {
            toolbar: 'BasicImage',
            height: 250
        });
    });

    // Reset CKEditor saat tombol Reset diklik
    $('button[type="reset"]').on('click', function(e) {
        // Ambil semua editor CKEditor
        for (var instanceName in CKEDITOR.instances) {
            if (CKEDITOR.instances.hasOwnProperty(instanceName)) {
                // Setel ulang ke nilai awal textarea
                var editor = CKEDITOR.instances[instanceName];
                var originalContent = editor.element.getText(); // Ambil nilai asli dari textarea
                editor.setData(originalContent); // Reset editor ke nilai asli
            }
        }
    });
</script>
