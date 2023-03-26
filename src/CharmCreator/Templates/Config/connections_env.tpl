---
name: Default connections config file for an environment
---
# +-------------------------------------------------------------------------+
# |    Environments/.../connections.yaml  Environment connections config    |
# +-------------------------------------------------------------------------+
# |                                                                         |
# | For environment: ENVIRONMENT_NAME                                       |
# |                                                                         |
# | In this star chart, you will define all connections to the databases    |
# | and other external sources and systems across the galaxy, such as       |
# | Redis or SMTP. Think of this file as the holocron that holds the        |
# | secrets to maintaining contact with the countless allies and resources  |
# | you'll need on your web development journey.                            |
# |                                                                         |
# | So harness the power of the Force and forge connections that will       |
# | stand the test of time, enabling you to bring balance and harmony to    |
# | your projects in a galaxy far, far away.                                |
# |                                                                         |
# | For more information on local config, see the docs:                     |
# |                                                                         |
# | https://neoground.com/docs/charm/core.config                            |
# |                                                                         |
# +-------------------------------------------------------------------------+

# ---------------------------------------------------------------------------
# :: SQL Database
# ---------------------------------------------------------------------------
database:
  # Should the database connection be enabled?
  enabled:   DB_ENABLED
  # Type of database
  driver:    DB_DRIVER
  # Authentication
  host:      'DB_HOST'
  username:  'DB_USER'
  password:  'DB_PASS'
  # Database
  database:  'DB_DATABASE'

# ---------------------------------------------------------------------------
# :: Redis
# ---------------------------------------------------------------------------
redis:
  # Should redis be enabled?
  enabled: REDIS_ENABLED
  # Authentication
  host: 'REDIS_HOST'
  port: REDIS_PORT
  password: 'REDIS_PASS'
