# Nextcloud Media Converter App

The **Nextcloud Media Converter** app enables you to automatically convert media files using FFmpeg in two ways:

1. **Automatic Conversion via [Nextcloud Workflows](https://nextcloud.com/workflow/)**  
   Automatically convert files as soon as they are created or uploaded.

2. **Manual Conversion via Conversion Batches**  
   Perform media conversions manually on selected files.

Both methods allow you to customize various rules for the conversion, including:  
- Deciding whether to keep or delete the original source file after conversion.  
- Specifying what should happen if a file with the same name as the converted output already exists.

### Screenshots

#### Conversion Batch UI
<img alt="Conversion Batch UI Screenshot" height="240" src="https://github.com/cwilby/nextcloud-workflow-media-converter/blob/main/screenshots/conversion-batch-ui.png" />

#### Flow Settings UI
<img alt="Flow Settings UI Screenshot" height="240" src="https://github.com/cwilby/nextcloud-workflow-media-converter/blob/main/screenshots/flow-settings-ui.png" />

---

## Requirements

To use this app, ensure you have the following prerequisites:
- **FFmpeg**: Must be installed on the server and discoverable by Nextcloud.
- **Background Jobs ("cron jobs")**: Properly configure background jobs in Nextcloud. Refer to the [Nextcloud Background Jobs documentation](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/background_jobs_configuration.html) for setup details.

> **Note:** Background jobs may take some time to detect and process new tasks.

---

## Limitations

- This app **does not work** with encryption enabled.
- Conversion capabilities and quality depend on the import filters supported by FFmpeg. Typically, audio and video files are supported for conversion, but functionality may vary depending on the codecs and formats in use.
