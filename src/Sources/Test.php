<?php

try {
	$client = new \Google_Client;
	$client->setAccessType('offline');
	$client->setApplicationName('IS Hnutí DUHA');
	$client->setClientId(\Katu\Config\Config::get('google', 'api', 'clientId'));
	$client->setClientSecret(\Katu\Config\Config::get('google', 'api', 'secret'));
	$client->setPrompt('consent');
	$client->setRedirectUri((string)\Katu\Tools\Routing\URL::getCurrent()->getWithoutQuery());
	$client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);

	$accessTokenPickle = new \Katu\Cache\Pickle([__CLASS__, __FUNCTION__, 'google', 'accessToken']);

	if ($request->getParam('code')) {
		$accessToken = $client->fetchAccessTokenWithAuthCode($request->getParam('code'));
		if ($accessToken['access_token'] ?? null) {
			$accessTokenPickle->set($accessToken);

			throw (new \Katu\Exceptions\RedirectException)->setUrl((string)\Katu\Tools\Routing\URL::getCurrent()->getWithoutQuery());
		}
	}

	$accessToken = $accessTokenPickle->get();

	try {
		$client->setAccessToken($accessToken);
	} catch (\Throwable $e) {
		throw new \Google\Service\Exception($e->getMessage());
	}

	try {
		if ($client->isAccessTokenExpired() && ($accessToken['refresh_token'] ?? null)) {
			$accessToken = $client->refreshToken($accessToken['refresh_token']);
			$accessTokenPickle->set($accessToken);
		}
	} catch (\Throwable $e) {
		throw new \Google\Service\Exception($e->getMessage());
	}

	$service = new \Google_Service_Sheets($client);
	$spreadsheetId = '1dXhQnZXr6_G0yRj4VdlvGoyC7gU9OgNVQlG5FTscXp0';
	$range = 'obce_lau';
	$res = $service->spreadsheets_values->get($spreadsheetId, $range);
	$rows = $res->getValues();

	foreach ($rows as &$row) {
		try {
			$row = array_combine($rows[0], $row);
		} catch (\Throwable $e) {
			// Nevermind.
		}
	}

	$rows = array_slice($rows, 1);

	var_dump($rows);die;
} catch (\Katu\Exceptions\RedirectException $e) {
	return $response->withRedirect($e->getUrl());
} catch (\Google\Service\Exception $e) {
	return $response->withRedirect($client->createAuthUrl());
}
