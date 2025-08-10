import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

;(window as any).Pusher = Pusher

const echo = new Echo({
  broadcaster: 'pusher',
  key: 'fed30f50518acf03eb5a', // Mesmo valor do PUSHER_APP_KEY
  //   wsHost: window.location.hostname,
  //   wsPort: 6001,
  forceTLS: false,
  //disableStats: true,
  //encrypted: false,
  cluster: 'sa1',
})

export default echo
