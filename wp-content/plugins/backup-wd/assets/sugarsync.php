<?php
/**
 * SugarSync class
 *
 * This source file can be used to communicate with SugarSync (http://sugarsync.com)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 *
 * License
 * Copyright (c), Daniel Huesken. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		Daniel Huesken <daniel@huesken-net.de>
 * @version		2.0.0
 *
 * @copyright	Copyright (c), Daniel Huesken. All rights reserved.
 * @license		GPL3 License
 */
class Buwd_SugarSync {
	const API_URL = 'https://api.sugarsync.com';

	private $appParams;

	private $folder = '';

	private $ProgressFunction = false;

	private $encoding = 'UTF-8';

	private $refreshToken = null;

	private $accessToken = null;

	public function __construct( $app_params, $refreshToken = null ) {
		$this->appParams = $app_params;
		if ( empty( $app_params['app_key'] ) ) {
			throw new SugarSyncException( "App Key is empty!" );
		}

		$this->encoding = mb_internal_encoding();
		if ( isset( $refreshToken ) and ! empty( $refreshToken ) ) {
			$this->setRefreshToken( $refreshToken );
			$accessToken = $this->getAccessToken();
			$this->setAccessToken( $accessToken );
		}

	}

	private function createRequest( $url, $data = '', $method = 'GET' ) {
		$url    = (string) $url;
		$method = (string) $method;

		// check auth token
		if ( empty( $this->accessToken ) ) {
			throw new SugarSyncException( 'Auth Token not set correctly!!' );
		} else {
			$headers[] = 'Authorization: ' . $this->accessToken;
		}
		$headers[] = 'Expect:';

		// init
		$curl = curl_init();
		//set otions
		curl_setopt( $curl, CURLOPT_URL, $url );
		//	curl_setopt( $curl, CURLOPT_USERAGENT, '' );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		if ( Buwd::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, Buwd::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( Buwd::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}

		if ( $method == 'POST' ) {
			$headers[] = 'Content-Type: application/xml; charset=UTF-8';
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $curl, CURLOPT_POST, true );
			$headers[] = 'Content-Length: ' . strlen( $data );
		} elseif ( $method == 'PUT' ) {
			if ( is_file( $data ) && is_readable( $data ) ) {
				$headers[]  = 'Content-Length: ' . filesize( $data );
				$datafilefd = fopen( $data, 'r' );
				curl_setopt( $curl, CURLOPT_PUT, true );
				curl_setopt( $curl, CURLOPT_INFILE, $datafilefd );
				curl_setopt( $curl, CURLOPT_INFILESIZE, filesize( $data ) );

				/* if ( function_exists( $this->ProgressFunction ) and defined( 'CURLOPT_PROGRESSFUNCTION' ) ) {
					curl_setopt( $curl, CURLOPT_NOPROGRESS, false );
					curl_setopt( $curl, CURLOPT_PROGRESSFUNCTION, $this->ProgressFunction );
					curl_setopt( $curl, CURLOPT_BUFFERSIZE, 1048576 );
				} */
			} else {
				throw new SugarSyncException( 'Is not a readable file:' . $data );
			}
		} elseif ( $method == 'DELETE' ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		} else {
			curl_setopt( $curl, CURLOPT_POST, false );
		}

		// set headers
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLINFO_HEADER_OUT, true );
		// execute
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );

		// fetch curl errors
		if ( curl_errno( $curl ) != 0 ) {
			throw new SugarSyncException( 'cUrl Error: ' . curl_error( $curl ) );
		}
		curl_close( $curl );
		if ( ! empty( $datafilefd ) && is_resource( $datafilefd ) ) {
			fclose( $datafilefd );
		}

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( false !== stripos( $curlgetinfo['content_type'], 'xml' ) && ! empty( $response ) ) {
				return simplexml_load_string( $response );
			} else {
				return $response;
			}
		} else {
			if ( $curlgetinfo['http_code'] == 401 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Authorization required.' );
			} elseif ( $curlgetinfo['http_code'] == 403 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' (Forbidden)  Authentication failed.' );
			} elseif ( $curlgetinfo['http_code'] == 404 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Not found' );
			} else {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] );
			}
		}
	}

	private function _read_cb( $curl, $fd, $length ) {
		$data = fread( $fd, $length );
		$len  = strlen( $data );
		if ( isset( $this->ProgressFunction ) ) {
			call_user_func( $this->ProgressFunction, $len );
		}

		return $data;
	}

	public function getAccessToken() {
		$auth = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<tokenAuthRequest>';
		$auth .= '<accessKeyId>' . $this->appParams['app_key'] . '</accessKeyId>';
		$auth .= '<privateAccessKey>' .  $this->appParams['secret_key']  . '</privateAccessKey>';
		$auth .= '<refreshToken>' . trim( $this->refreshToken ) . '</refreshToken>';
		$auth .= '</tokenAuthRequest>';
		// init
		$curl = curl_init();
		//set options
		curl_setopt( $curl, CURLOPT_URL, self::API_URL . '/authorization' );
		//	curl_setopt( $curl, CURLOPT_USERAGENT, '' );
		if ( ini_get( 'open_basedir' ) == '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( Buwd::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, Buwd::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( Buwd::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/xml; charset=UTF-8',
			'Content-Length: ' . strlen( $auth )
		) );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		// execute
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors
		if ( curl_errno( $curl ) != 0 ) {
			throw new SugarSyncException( 'cUrl Error: ' . curl_error( $curl ) );
		}

		curl_close( $curl );

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( preg_match( '/Location:(.*?)\r/i', $response, $matches ) ) {
				return trim( $matches[1] );
			}
		} else {
			if ( $curlgetinfo['http_code'] == 401 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Authorization required.' );
			} elseif ( $curlgetinfo['http_code'] == 403 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' (Forbidden)  Authentication failed.' );
			} elseif ( $curlgetinfo['http_code'] == 404 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Not found' );
			} else {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] );
			}
		}
	}

	public function setAccessToken( $token ) {
		$this->accessToken = $token;
	}

	public function getRefreshToken( $email, $password ) {
		$auth = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<appAuthorization>';
		$auth .= '<username>' . mb_convert_encoding( $email, 'UTF-8', $this->encoding ) . '</username>';
		$auth .= '<password>' . mb_convert_encoding( $password, 'UTF-8', $this->encoding ) . '</password>';
		$auth .= '<application>' . $this->appParams['app_id'] . '</application>';
		$auth .= '<accessKeyId>' . $this->appParams['app_key'] . '</accessKeyId>';
		$auth .= '<privateAccessKey>' .  $this->appParams['secret_key'] . '</privateAccessKey>';
		$auth .= '</appAuthorization>';
		// init
		$curl = curl_init();
		//set options
		curl_setopt( $curl, CURLOPT_URL, self::API_URL . '/app-authorization' );
		curl_setopt( $curl, CURLOPT_USERAGENT, 'BW' );
		if ( ini_get( 'open_basedir' ) == '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( Buwd::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, Buwd::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( Buwd::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}

		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/xml; charset=UTF-8',
			'Content-Length: ' . strlen( $auth )
		) );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		// execute
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors

		if ( curl_errno( $curl ) != 0 ) {
			throw new SugarSyncException( 'cUrl Error: ' . curl_error( $curl ) );
		}

		curl_close( $curl );

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( preg_match( '/Location:(.*?)\r/i', $response, $matches ) ) {
				return trim( $matches[1] );
			}

		} else {
			if ( $curlgetinfo['http_code'] == 401 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Authorization required.' );
			} elseif ( $curlgetinfo['http_code'] == 403 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' (Forbidden)  Authentication failed.' );
			} elseif ( $curlgetinfo['http_code'] == 404 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Not found' );
			} else {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] );
			}
		}
	}

	public function setRefreshToken( $token ) {
		$this->refreshToken = $token;
	}

	public function create_account( $email, $password ) {
		$auth = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<user>';
		$auth .= '<email>' . mb_convert_encoding( $email, 'UTF-8', $this->encoding ) . '</email>';
		$auth .= '<password>' . mb_convert_encoding( $password, 'UTF-8', $this->encoding ) . '</password>';
		$auth .= '<accessKeyId>' . $this->appParams['app_key'] . '</accessKeyId>';
		$auth .= '<privateAccessKey>' .  $this->appParams['secret_key']  . '</privateAccessKey>';
		$auth .= '</user>';
		// init
		$curl = curl_init();
		//set options
		curl_setopt( $curl, CURLOPT_URL, 'https://provisioning-api.sugarsync.com/users' );
		//curl_setopt( $curl, CURLOPT_USERAGENT, '' );
		if ( ini_get( 'open_basedir' ) == '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( Buwd::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, Buwd::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( Buwd::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/xml; charset=UTF-8',
			'Content-Length: ' . strlen( $auth )
		) );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		// execute
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors
		if ( curl_errno( $curl ) != 0 ) {
			throw new SugarSyncException( 'cUrl Error: ' . curl_error( $curl ) );
		}

		curl_close( $curl );

		if ( $curlgetinfo['http_code'] == 201 ) {
			throw new SugarSyncException( 'Account created.' );
		} else {
			if ( $curlgetinfo['http_code'] == 400 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) );
			} elseif ( $curlgetinfo['http_code'] == 401 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' Developer credentials cannot be verified. Either a developer with the specified accessKeyId does not exist or the privateKeyID does not match an assigned accessKeyId.' );
			} elseif ( $curlgetinfo['http_code'] == 403 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) );
			} elseif ( $curlgetinfo['http_code'] == 503 ) {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) );
			} else {
				throw new SugarSyncException( 'Http Error: ' . $curlgetinfo['http_code'] );
			}
		}
	}

	public function chdir( $folder, $root = '' ) {
		$folder = rtrim( $folder, '/' );
		if ( substr( $folder, 0, 1 ) == '/' || empty( $this->folder ) ) {
			if ( ! empty( $root ) ) {
				$this->folder = $root;
			} else {
				throw new SugarSyncException( 'chdir: root folder must set!' );
			}
		}
		$folders = explode( '/', $folder );
		foreach ( $folders as $dir ) {
			if ( $dir == '..' ) {
				$contents = $this->createRequest( $this->folder );
				if ( ! empty( $contents->parent ) ) {
					$this->folder = $contents->parent;
				}
			} elseif ( ! empty( $dir ) && $dir != '.' ) {
				$isdir    = false;
				$contents = $this->getcontents( 'folder' );
				foreach ( $contents->collection as $collection ) {
					if ( strtolower( $collection->displayName ) == strtolower( $dir ) ) {
						$isdir        = true;
						$this->folder = $collection->ref;
						break;
					}
				}
				if ( ! $isdir ) {
					throw new SugarSyncException( 'chdir: Folder ' . $folder . ' not exitst' );
				}
			}
		}

		return $this->folder;
	}

	public function showdir( $folderid ) {
		$showfolder = '';
		while ( $folderid ) {
			$contents   = $this->createRequest( $folderid );
			$showfolder = $contents->displayName . '/' . $showfolder;
			if ( isset( $contents->parent ) ) {
				$folderid = $contents->parent;
			} else {
				break;
			}
		}

		return $showfolder;
	}

	public function mkdir( $folder, $root = '' ) {
		$savefolder = $this->folder;
		$folder     = rtrim( $folder, '/' );
		if ( substr( $folder, 0, 1 ) == '/' || empty( $this->folder ) ) {
			if ( ! empty( $root ) ) {
				$this->folder = $root;
			} else {
				throw new SugarSyncException( 'mkdir: root folder must set!' );
			}
		}

		$folders = explode( '/', $folder );
		foreach ( $folders as $dir ) {
			if ( $dir == '..' ) {
				$contents = $this->createRequest( $this->folder );
				if ( ! empty( $contents->parent ) ) {
					$this->folder = $contents->parent;
				}
			} elseif ( ! empty( $dir ) && $dir != '.' ) {
				$isdir    = false;
				$contents = $this->getcontents( 'folder' );
				foreach ( $contents->collection as $collection ) {
					if ( strtolower( $collection->displayName ) == strtolower( $dir ) ) {
						$isdir        = true;
						$this->folder = $collection->ref;
						break;
					}
				}
				if ( ! $isdir ) {
					$request  = $this->createRequest( $this->folder, '<?xml version="1.0" encoding="UTF-8"?><folder><displayName>' . mb_convert_encoding( $dir, 'UTF-8', $this->encoding ) . '</displayName></folder>', 'POST' );
					$contents = $this->getcontents( 'folder' );
					foreach ( $contents->collection as $collection ) {
						if ( strtolower( $collection->displayName ) == strtolower( $dir ) ) {
							$isdir        = true;
							$this->folder = $collection->ref;
							break;
						}
					}
				}
			}
		}
		$this->folder = $savefolder;

		return true;
	}

	public function user() {
		return $this->createRequest( self::API_URL . '/user' );
	}

	public function get( $url ) {
		return $this->createRequest( $url, '', 'GET' );
	}

	public function download( $url ) {
		return $this->createRequest( $url . '/data' );
	}

	public function delete( $url ) {
		return $this->createRequest( $url, '', 'DELETE' );
	}

	public function getcontents( $type = '', $start = 0, $max = 500 ) {
		$parameters = '';
		if ( strtolower( $type ) == 'folder' || strtolower( $type ) == 'file' ) {
			$parameters .= 'type=' . strtolower( $type );
		}
		if ( ! empty( $start ) && is_integer( $start ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'start=' . $start;

		}
		if ( ! empty( $max ) && is_integer( $max ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'max=' . $max;
		}

		$request = $this->createRequest( $this->folder . '/contents?' . $parameters );

		return $request;
	}

	public function upload( $file, $name = '' ) {
		if ( empty( $name ) ) {
			$name = basename( $file );
		}
		$xmlrequest = '<?xml version="1.0" encoding="UTF-8"?>';
		$xmlrequest .= '<file>';
		$xmlrequest .= '<displayName>' . mb_convert_encoding( $name, 'UTF-8', $this->encoding ) . '</displayName>';
		$xmlrequest .= '<mediaType>' . $this->mime_content_type( $file ) . '</mediaType>';

		$xmlrequest .= '</file>';
		$request  = $this->createRequest( $this->folder, $xmlrequest, 'POST' );
		$getfiles = $this->getcontents( 'file' );
		foreach ( $getfiles->file as $getfile ) {
			if ( $getfile->displayName == $name ) {
				$this->createRequest( $getfile->ref . '/data', $file, 'PUT' );

				return $getfile->ref;
			}
		}
	}

	public function setProgressFunction( $function ) {
		if ( function_exists( $function ) ) {
			$this->ProgressFunction = $function;
		} else {
			$this->ProgressFunction = false;
		}
	}

	public function mime_content_type( $file ) {
		$mime_types = array(
			'txt'  => 'text/plain',
			'htm'  => 'text/html',
			'html' => 'text/html',
			'php'  => 'text/html',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'xml'  => 'application/xml',
			'swf'  => 'application/x-shockwave-flash',
			'flv'  => 'video/x-flv',

			// images
			'png'  => 'image/png',
			'jpe'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'svg'  => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip'  => 'application/zip',
			'gz'   => 'application/gzip',
			'bz2'  => 'application/x-bzip',
			'tar'  => 'application/x-tar',
			'rar'  => 'application/x-rar-compressed',
			'exe'  => 'application/x-msdownload',
			'msi'  => 'application/x-msdownload',
			'cab'  => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3'  => 'audio/mpeg',
			'qt'   => 'video/quicktime',
			'mov'  => 'video/quicktime',

			// adobe
			'pdf'  => 'application/pdf',
			'psd'  => 'image/vnd.adobe.photoshop',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',

			// ms office
			'doc'  => 'application/msword',
			'rtf'  => 'application/rtf',
			'xls'  => 'application/vnd.ms-excel',
			'ppt'  => 'application/vnd.ms-powerpoint',

			// open office
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$f_name = explode( '.', $file );
		$ext    = strtolower( array_pop( $f_name ) );
		if ( array_key_exists( $ext, $mime_types ) ) {
			return $mime_types[ $ext ];
		} elseif ( function_exists( 'finfo_open' ) ) {
			$finfo    = finfo_open( FILEINFO_MIME );
			$mimetype = finfo_file( $finfo, $file );
			finfo_close( $finfo );

			return $mimetype;
		} else {
			return 'application/octet-stream';
		}
	}

}


/**
 * SugarSync Exception class
 *
 * @author  Daniel Huesken <daniel@huersken-net.de>
 */
class SugarSyncException extends Exception {
}