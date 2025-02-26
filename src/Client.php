<?php


namespace GNOffice\DirectCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GNOffice\DirectCloud\Exceptions\BadRequest;

class Client
{
    /**
     * @var array|mixed
     */
    private ClientInterface $client;
    protected ?string $service = null;
    protected ?string $serviceKey = null;
    protected ?string $accessKey = null;
    protected ?string $code = null;
    protected ?string $id = null;
    protected ?string $password = null;
    private ?TokenProvider $tokenProvider = null;

    public function __construct(array|string|TokenProvider $accessInformation, ?ClientInterface $client = null)
    {
        if (is_array($accessInformation)) {
            if (count($accessInformation) === 3) {
                [$this->service, $this->serviceKey, $this->accessKey] = $accessInformation;
                $this->tokenProvider = new AccessKeyTokenProvider($this->service, $this->serviceKey, $this->accessKey);
            } elseif (count($accessInformation) === 5) {
                [$this->service, $this->serviceKey, $this->code, $this->id, $this->password] = $accessInformation;
                $this->tokenProvider = new AccountInfoTokenProvider($this->service, $this->serviceKey, $this->code,
                    $this->id, $this->password);
            }
        }

        if (is_string($accessInformation)) {
            $this->tokenProvider = new InMemoryTokenProvider($accessInformation);
        }

        if ($accessInformation instanceof TokenProvider) {
            $this->tokenProvider = $accessInformation;
        }

        $this->client = $client ?? new GuzzleClient([
            'base_uri' => 'https://api.directcloud.jp'
        ]);

    }

    public function getList(string $node = '1{2'): array
    {
        $url = '/openapi/v2/folders/lists';
        $parameters = [
            'node' => $node,
            'limit' => 1000,
        ];

        $response = $this->v2Request('GET', $url, 'query', $parameters);

        return $response['data'];
    }

    public function getFolderInfo(string $node, int $dirSeq): array
    {
        $url = '/openapp/v1/folders/index/'.$node.'/'.$dirSeq;

        return $this->v1Request('GET', $url);
    }

    public function getFileInfo(string $node, int $fileSeq): array
    {
        $url = '/openapp/v1/files/index/'.$node.'/'.$fileSeq;

        return $this->v1Request('GET', $url);
    }

    public function moveFolder(string $dstNode, string $srcNode, string $node): bool
    {
        $url = '/openapi/v2/folders/move';
        $parameters = [
            'dst_node' => $dstNode,
            'src_node' => $srcNode,
            'node' => $node,
        ];

        $response = $this->v2Request('PUT', $url, 'form_params', $parameters);

        return $response['result'] === 'success';
    }

    public function download(int $fileSeq)
    {
        $url = '/openapi/v2/files/download';
        $parameters = [
            'file_seq' => $fileSeq,
            'flag_direct' => 'Y',
        ];

        return $this->v2Request('POST', $url, 'json', $parameters);
    }

    public function upload(string $node, $file, string $name = null): array
    {
        $url = '/openapi/v2/files/upload/sync';

        $parameters = [
            [
                'name' => 'node',
                'contents' => $node,
            ],
            [
                'name' => 'file',
                'contents' => $file,
            ],
        ];

        if ($name !== null) {
            $parameters[] = [
                'name' => 'name',
                'contents' => $name,
            ];
        }

        return $this->v2Request('POST', $url, 'multipart', $parameters);
    }


    public function copyFile(string $dstNode, string $srcNode, int $fileSeq): array
    {
        $url = '/openapi/v2/files/copy';
        $parameters = [
            'dst_node' => $dstNode,
            'src_node' => $srcNode,
            'file_seq' => $fileSeq,
        ];

        return $this->v2Request('PUT', $url, 'form_params', $parameters);
    }

    public function moveFile(string $dstNode, string $srcNode, int $fileSeq): bool
    {
        $url = '/openapi/v2/files/move';
        $parameters = [
            'dst_node' => $dstNode,
            'src_node' => $srcNode,
            'file_seq' => $fileSeq,
        ];

        $response = $this->v2Request('PUT', $url, 'form_params', $parameters);

        return $response['result'] === 'success';
    }

    public function createFolder(string $node, string $name): array
    {
        $url = '/openapp/v1/folders/create/' . $node;

        $parameters = [
            'name' => $name
        ];

        return $this->v1Request('POST', $url, 'form_params', $parameters);
    }

    public function renameFolder(string $node, string $name): bool
    {
        $url = '/openapp/v1/folders/rename/' . $node;

        $parameters = [
            'name' => $name
        ];

        $response = $this->v1Request('POST', $url, 'form_params', $parameters);

        return $response['success'];
    }

    public function deleteFolder(string $node): bool
    {
        $url = '/openapp/v1/folders/delete/' . $node;

        $response = $this->v1Request('POST', $url);

        return $response['success'];
    }

    public function renameFile(string $node, int $fileSeq, string $name): bool
    {
        $url = '/openapp/v1/files/rename/'.$node;

        $parameters = [
            'file_seq' => $fileSeq,
            'name'     => $name
        ];

        $response = $this->v1Request('POST', $url, 'form_params', $parameters);

        return $response['success'];
    }

    public function deleteFile(string $node, int $fileSeq): bool
    {
        $url = '/openapp/v1/files/delete/'.$node;

        $parameters = [
            'file_seq' => $fileSeq,
        ];

        $response = $this->v1Request('POST', $url, 'form_params', $parameters);

        return $response['success'];
    }

    public function createLink(
        string $targetType,
        int $targetSeq,
        string $expirationDate,
        string $password,
        string $viewOption = 'both',
        $limitCount = 0
    ): string
    {
        $url = '/openapp/v1/links/create';

        $parameters = [
            'target_type' => $targetType,
            'target_seq'      => $targetSeq,
            'view_option'     => $viewOption,
            'expiration_date' => $expirationDate,
            'limit_count'     => $limitCount,
            'password'        => $password,
        ];
        $response = $this->v1Request('POST', $url, 'form_params', $parameters);

        return $response['url'];
    }

    protected function v1Request(string $method, string $endpoint, string $requestOption = null, $parameters = [])
    {
        $options = [
            'headers' => [
                'access_token' => $this->tokenProvider->getToken()
            ],
            'query'   => [
                'lang' => 'eng',
            ],
        ];

        if ($requestOption) {
            $options[$requestOption] = $parameters;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);

            $json = json_decode($response->getBody(), true);

            if ($json['success'] === false) {
                throw new BadRequest($response);
            }

        } catch (ClientException $exception) {
            throw new BadRequest($exception->getResponse());
        }

        return $json ?? [];
    }

    protected function v2Request(string $method, string $endpoint, string $requestOption, $parameters = [], $extraHeaders = [])
    {
        $options = [
            'headers' => [
                'Access-Token' => $this->tokenProvider->getToken(),
                'Lang' => 'en',
            ],
        ];

        if (count($extraHeaders) > 0) {
            $options['headers'] = array_merge($options['headers'], $extraHeaders);
        }

        $options[$requestOption] = $parameters;

        try {
            $response = $this->client->request($method, $endpoint, $options);
        } catch (ClientException $e) {
            throw $e;
        }

        $body = $response->getBody();
        $json = json_decode($body, true);

        if (isset($json['result'])) {
            if ($json['result'] !== 'success') {
                throw new BadRequest($response);
            }
        } else {
            return (string) $body;
        }

        return $json ?? [];
    }
}
