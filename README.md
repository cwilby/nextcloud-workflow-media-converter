# Nextcloud Media Converter app

This app allows you automatically convert media with FFmpeg in a couple of useful ways:

1.  Automatically via [Workflows](https://nextcloud.com/workflow/) to convert files as soon as they are created.
2.  Manually via conversion batches.

Both methods allow you to configure a number of rules, such as whether the source file should be kept or deleted, and what to do with conversion output when a file exists with the same name.

<img height="240" src="https://github.com/cwilby/nextcloud-workflow-media-converter/blob/main/screenshots/conversion-batch-ui.png" />
<img height="240" src="https://github.com/cwilby/nextcloud-workflow-media-converter/blob/main/screenshots/flow-settings-ui.png" />

## Requirements

- FFmpeg must be installed on the server and the binary must detectable by Nextcloud.

- [Background jobs](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/background_jobs_configuration.html) (or "cron jobs") must be setup correctly.  Note that it may take up to 5 minutes for any conversions to begin, depending on how often your background/cron jobs are scheduled to run.

## Limitations

This app does not work if encryption is enabled.  

Since FFmpeg is used for conversion, its import filters decide the possibility and quality of conversion. Essentially, video and audio files can be converted. Due to a high number of codecs and formats, by default we feed anything to FFmpeg matching the rules specified in the workflow.
