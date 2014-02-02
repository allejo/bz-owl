About of matchServices add-on:

This add-on handles competing events (e.g. matches) and is designed for use by other code.
However please make sure you stick to the following guidelines.


Usage:

It is save to call public functions within matchServices but don't call code of specific versions.
Instead check if event type, and in rare cases event version, are suited for your request by using getEventType and getEventVersion.
You must not change the add-on or copy code outside and modify there to fit your needs. It would likely cause incompatibility.
If there is a code problem fix it in repository or report it.
If you think a specific function is missing for your usage discuss the case.
Adding your own event version code is save in case you use a custom prefix that contains an underscore at its end for your folder inside versions.
