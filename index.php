<?php
	/*
	 *	Yandex.vision
	 *
	 *	curl -d "{\"yandexPassportOauthToken\":\"< IAMToken >\"}" "https://iam.api.cloud.yandex.net/iam/v1/tokens" > iamtoken.txt
	 *
	 *	https://console.cloud.yandex.ru/folders/b1g3ecuir8nbjtavcif0
	 *	b1g3ecuir8nbjtavcif0
	 *
	 *	https://cloud.yandex.ru/docs/vision/operations/ocr/text-detection
	 *	https://cloud.yandex.ru/docs/vision/api-ref/Vision/batchAnalyze
	 */

	const files = [
		'yandex-vision-content' => __DIR__."/../yandex-vision-content/",
		'images_dir' => __DIR__."/../images/",
		'iamtoken' => __DIR__."/iamtoken.txt",
		'base64_output' => __DIR__."/yandex-base64-output.txt",
		'jsonbody' => __DIR__."/body.json",
		'outputcontent' => __DIR__."/output.json",
	];

	$token = json_decode(file_get_contents(files['iamtoken']))->iamToken;

	if (!is_dir(files['yandex-vision-content']))
		mkdir(files['yandex-vision-content']);

	$scan = array_diff(scandir(files['images_dir']), ['.', '..']);

	foreach ($scan as $value)
	{
		if (is_dir(files['images_dir'].$value))
		{
			if (!is_dir(files['yandex-vision-content'].$value))
				mkdir(files['yandex-vision-content'].$value);

			$images = scandir(files['images_dir'].$value);

			foreach($images as $image)
			{
				if (is_file(files['images_dir'].$value."/".$image))
				{
					copy(files['images_dir'].$value."/".$image, files['yandex-vision-content'].$value."/".$image);
					
					$content = file_get_contents(files['yandex-vision-content'].$value."/".$image);
					$exp = explode(".", $image);

					$output = base64_encode($content);

					$json_body = [
						"folderId" => "b1g3ecuir8nbjtavcif0",
						"analyze_specs" => [
							"content" => $output,
							"features" => [
								"type" => "TEXT_DETECTION",
					            "text_detection_config" => [
					                "language_codes" => ["*"]
					            ]
							]
						]
					];

					$ch = curl_init('https://vision.api.cloud.yandex.net/vision/v1/batchAnalyze');
					curl_setopt($ch, CURLOPT_HTTPHEADER, [
						'Content-Type:application/json',
						"Authorization: Bearer $token"
					]);

					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_body, JSON_UNESCAPED_UNICODE)); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_HEADER, false);
					$res = curl_exec($ch);
					curl_close($ch);
					 
					$res = json_encode($res, JSON_UNESCAPED_UNICODE);

					if (!file_exists(files['yandex-vision-content'].$value."/".$exp[0].".json"));
						touch(files['yandex-vision-content'].$value."/".$exp[0].".json");

					file_put_contents(files['yandex-vision-content'].$value."/".$exp[0].".json", $res);

				}
			}
		}
	}