# Nextcloud Media Converter app

This app lets Nextcloud automatically convert media using FFmpeg. By utilizing the workflow engine it allows Nextcloud users to define rules upon which various media are queued for conversion with FFmpeg.  

Depending on the selected behaviour the source file can either be kept or deleted and the output can either be preserved by increasing a number added to the filename or overwritten.

## Requirements

FFmpeg must be installed on the server and the binary must detectable by Nextcloud.

## Limitations

This app does not work if encryption is enabled.

Since FFmpeg is used for conversion, its import filters decide the possibility and quality of conversion. Essentially, video and audio files can be converted. Due to a high number of codecs and formats, by default we feed anything to LibreOffice matching the rules specified in the workflow.
