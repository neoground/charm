---
name: Default main config file for an environment
---
# +-------------------------------------------------------------------------+
# |    Environments/.../main.yaml         Environment main configuration    |
# +-------------------------------------------------------------------------+
# |                                                                         |
# | For environment: ENVIRONMENT_NAME                                       |
# |                                                                         |
# | In this YAML file, you can override individual config values for your   |
# | intergalactic adventures. If the app environment is set to the above    |
# | in app/app.env, values in this file will be preferred, just like how    |
# | R2-D2 prefers to save the day.                                          |
# |                                                                         |
# | If a value is not set, the standard config value in                     |
# | app/Config/main.yaml will be used, as reliable as a trusty X-Wing.      |
# | This same principle applies to all other config files, with the         |
# | environment config file simply requiring the same name.                 |
# |                                                                         |
# | So strap in and prepare to configure your local environment, and        |
# | may the Force be with you!                                              |
# |                                                                         |
# | For more information on local config, see the docs:                     |
# |                                                                         |
# | https://neoground.com/docs/charm/core.config                            |
# |                                                                         |
# +-------------------------------------------------------------------------+

# ---------------------------------------------------------------------------
# :: Debug
# ---------------------------------------------------------------------------
debug:
  # Debug mode activated?
  debugmode: DEBUG_MODE
  # Show debug bar on views
  show_debugbar: DEBUG_MODE
  # Log debugbar data for a history
  log_debugbar: false
  # The editor to open files in (for Whoops page)
  # Available: sublime, textmate, emacs, macvim, phpstorm, idea,
  #            vscode, atom, espresso, xdebug, netbeans
  editor: atom

# ---------------------------------------------------------------------------
# :: Output
# ---------------------------------------------------------------------------
output:
  # Error style (default, view, json, exception)
  error_style: ERROR_STYLE

# ---------------------------------------------------------------------------
# :: Request specific
# ---------------------------------------------------------------------------
request:
  # Force HTTPS in URL generation, even if HTTP request was detected?
  force_https: false
  # Base URL to index as fallback
  base_url: BASE_URL
