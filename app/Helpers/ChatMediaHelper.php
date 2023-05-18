<?php

namespace App\Helpers;

use App\Models\MetaAccessToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\File;
use Illuminate\Support\Facades\File as LocalFile;
use Illuminate\Support\Facades\Storage;

class ChatMediaHelper
{
    /**
     * It takes the media ID, the company ID, the file type and the extension of the file and returns the
     * URL of the file
     * 
     * @param companyID The company ID of the company that owns the media.
     * @param mediaID The ID of the media you want to download.
     * @param fileType The type of file you're uploading. This can be either "image", "video", "audio", or
     * "document".
     * @param extension The file extension of the media file.
     * 
     * @return A URL to the media file.
     */
    public static function whatsApp($companyID, $mediaID, $fileType, $extension)
    {
        $url = "https://graph.facebook.com/v15.0/";

        $token = MetaAccessToken::where([
            'company_id' => $companyID,
            'active' => true
        ])->first();

        if ($token) {
            $response = Http::withToken($token->access_token)->get($url . $mediaID);

            if ($response->successful()) {
                $media = Http::withToken($token->access_token)->get($response['url']);

                if ($fileType == 'document') {
                    $mime = explode(".", $extension);
                    $ext = end($mime);
                } else {
                    $mime = explode("/", $extension);
                    $ext = $mime[1];
                }


                $filename = time() . '.' . $ext;

                $fp = fopen("/var/www/html/callcenter/public/Media/" . $filename, 'x');
                fwrite($fp, $media);
                fclose($fp);

                Storage::putFileAs('call_center/ChatMedia', new File(public_path('Media/' . $filename)), $filename,  'public');

                return "https://goipspace.fra1.digitaloceanspaces.com/call_center/ChatMedia/" . $filename;
            }
        }
    }

    /**
     * It takes a URL, downloads the file, uploads it to DigitalOcean Spaces, and returns the new URL
     * 
     * @param fileUrl The URL of the file you want to download.
     * 
     * @return A string
     */
    public static function twitter($fileUrl)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $fileUrl . '?oauth_consumer_key=4n5taLtdVuQItKEDoMGikTcax&oauth_token=1580540819915710464-2o4enA8mFYvcnwszhTcaKeZCcaFCA8&oauth_signature_method=HMAC-SHA1&oauth_timestamp=1666967315&oauth_nonce=n9pvygC6BUZ&oauth_version=1.0&oauth_signature=PALKjCP%252FSo5Y82CwSawHoUk%252BiB0%253D',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $ext = explode('.', $fileUrl);

        $filename = time() . '.' . end($ext);

        $fp = fopen("/var/www/html/callcenter/public/Media/" . $filename, 'x');
        fwrite($fp, $response);
        fclose($fp);

        Storage::putFileAs('call_center/ChatMedia', new File(public_path('Media/' . $filename)), $filename,  'public');

        self::delete_file($filename);

        return "https://fra1.digitaloceanspaces.com/call_center/ChatMedia/" . $filename;
    }

    /**
     * It downloads a file from a URL and saves it to a local directory
     * 
     * @param fileUrl The URL of the file you want to download.
     * 
     * @return The file path of the file that was downloaded.
     */
    public static function get_file($fileUrl)
    {
        $file_name = explode('.', $fileUrl);

        $ext = end($file_name);

        $filename = time() . '.' . $ext;

        $file = '/var/www/html/callcenter/public/Media/' . $filename;

        file_put_contents($file, file_get_contents($fileUrl));

        return $filename;
    }

    /**
     * It checks if the file exists, if it does, it deletes it, if it doesn't, it returns null
     * 
     * @param filename The name of the file you want to delete.
     * 
     * @return the file name of the file that was uploaded.
     */
    public static function delete_file($filename)
    {
        if (LocalFile::exists(public_path('Media/' . $filename))) {
            LocalFile::delete(public_path('Media/' . $filename));
        } else {
            dd('File does not exists.');
        }
    }

    public static function upload_to_whatsapp($companyID, $phoneNoID, $filename)
    {
        $url = "https://graph.facebook.com/v15.0/$phoneNoID/media";

        $file = fopen('/var/www/html/callcenter/public/Media/' . $filename, 'r');

        $token = MetaAccessToken::where([
            'company_id' => $companyID,
            'active' => true
        ])->first();

        if ($token) {

            $response = Http::withToken($token->access_token)->attach(
                'file',
                $file
            )->post($url, [
                'messaging_product' => 'whatsapp'
            ]);

            if ($response->successful()) {

                return $response['id'];
            }
            return null;
        }
        return null;
    }
}