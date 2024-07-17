import { createClient } from 'webdav'

import { getRequestToken } from '@nextcloud/auth'
import { generateRemoteUrl } from '@nextcloud/router'

// init webdav client on default dav endpoint
const clientDav = createClient(generateRemoteUrl('dav'),
	{ headers: { requesttoken: getRequestToken() || '' } },
)

export default clientDav
