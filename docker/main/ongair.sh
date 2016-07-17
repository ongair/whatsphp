#!/bin/sh

### BEGIN INIT INFO
# Provides:          ongair
# Required-Start:    $remote_fs
# Required-Stop:     $remote_fs
# Should-Start:      $all
# Should-Stop:       $all
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: WhatsApp agent runner
# Description:       Ongair runs a whatsapp agent service
#
### END INIT INFO

set -e

. /lib/lsb/init-functions

DAEMON=/whatsapp/service.sh
NAME=ongair
DESC="ongair daemon"
PID="/run/$NAME.pid"

# Check if DAEMON binary exist
[ -f $DAEMON ] || exit 0

[ -f "/etc/default/$NAME" ] && . /etc/default/$NAME

case "$1" in
  start)
    log_daemon_msg "Starting $DESC" "$NAME"
    if start-stop-daemon --start --quiet --oknodo --pidfile $PID --exec $DAEMON 1>/dev/null
    then
      log_end_msg 0
    else
      log_end_msg 1
    fi
    ;;
  stop)
    log_daemon_msg "Stopping $DESC" "$NAME"
    if start-stop-daemon --retry TERM/5/KILL/5 --oknodo --stop --quiet --pidfile $PID 1>/dev/null
    then
      log_end_msg 0
    else
      log_end_msg 1
    fi
    ;;
  reload)
    log_daemon_msg "Reloading $DESC configuration" "$NAME"
    if start-stop-daemon --stop --signal HUP --quiet --oknodo --pidfile $PID --exec $DAEMON 1>/dev/null
    then
      log_end_msg 0
    else
      log_end_msg 1
  fi
    ;;
  restart|force-reload)
    log_daemon_msg "Restarting $DESC" "$NAME"
    start-stop-daemon --retry TERM/5/KILL/5 --oknodo --stop --quiet --pidfile $PID 1>/dev/null
    if start-stop-daemon --start --quiet --oknodo --pidfile $PID --exec $DAEMON 1>/dev/null
    then
      log_end_msg 0
    else
      log_end_msg 1
    fi
    ;;
  syntax)
    $DAEMON
    ;;
  status)
    status_of_proc -p $PID $DAEMON $NAME
    ;;
  *)
    log_action_msg "Usage: /etc/init.d/$NAME {start|stop|reload|restart|force-reload|syntax|status}"
    ;;
esac

exit 0