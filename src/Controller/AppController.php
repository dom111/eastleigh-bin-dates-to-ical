<?php

namespace App\Controller;

use App\Service\PdfDownloader;
use DateTime;
use DateInterval;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/", name="app-")
 */
class AppController extends AbstractController
{
    private PdfDownloader $downloader;
    /**
     * @var Environment
     */
    private Environment $twig;

    public function __construct(PdfDownloader $downloader, Environment $twig)
    {
        $this->downloader = $downloader;
        $this->twig = $twig;
    }

    /**
     * @Route(
     *     "{uprn}.ics",
     *     name="feed",
     *     requirements={"uprn"="^\d{12}$"}
     * )
     */
    public function feed(string $uprn): Response
    {
        // Non-web testing:
//        $url = __DIR__ . '/../../resources/bindates.pdf';
        // $url = 'https://wamp.eastleigh.gov.uk/getwastecalendar.aspx?UPRN=' . $uprn;
        $url = 'https://my.eastleigh.gov.uk/apex/EBC_Waste_Calendar?UPRN=' . $uprn;
        $data = $this->downloader->parse($url);

        $string = '';
        $current = null;
        $dates = [];

        // TODO: eventually make this configurable or handled via URL params?
        $reminders = [
            'reminder' => [
                'prefix' => 'Put out ',
                'when' => '-PT6H',
            ],
            'collection' => [
                'prefix' => 'Collecting ',
                'when' => 'PT7H',
            ],
        ];

        foreach ($data->getPages() as $page) {
            foreach ($page->getDataTm() as $data) {
                if (strpos($data[1], 'Black Household') !== false) {
                    $current = 'household';
                }
                elseif (strpos($data[1], 'Glass Box') !== false) {
                    $current = 'recycling, glass';
                }
                elseif (strpos($data[1], 'Garden') !== false) {
                    $current = 'garden waste';
                }

                if (preg_match('/^[MTWFS]$/', $data[1])) {
                    $string = $data[1] . ' ';
                }
                elseif (preg_match('/^(Mon|T(ue|hu)|Wed|Fri|S(at|un))$/', $data[1])) {
                    $string = $data[1] . ' ';
                }

                if (preg_match('/^([ou]n|ue|ed|hu|ri|at)$/', $data[1])) {
                    $string = trim($string) . $data[1] . ' ';
                }
                elseif (preg_match('/^[MTWFS]$/', $string)) {
                    $string = '';
                }

                if (preg_match('/^\d+/', $data[1])) {
                    $string = trim($string) . (preg_match('/\d+ $/', $string) ? '' : ' ') . $data[1] . ' ';
                }

                if (preg_match('/^((Jan|Febr)uary|Ma(rch|y)|April|Ju(ne|ly)|August|((Sept|Nov|Dec)em|Octo)ber)/', $data[1])) {
                    $string .= $data[1];

                    if ($current !== null) {
                        $date = new DateTime($string, new DateTimeZone('UTC'));

                        // if we're parsing January in December we need to correct it.
                        if ($date->getTimestamp() < time()) {
                            $date = new DateTime($string . ' ' . (intval($date->format('Y')) + 1));
                        }

                        $date->setTimezone(new DateTimeZone('Europe/London'));

                        foreach ($reminders as $key => $reminder) {
                            $reminderDate = clone $date;

                            if (! array_key_exists($key, $dates)) {
                                $dates[$key] = array_merge([
                                    'suffix' => ' waste',
                                    'data' => [],
                                ], $reminder);
                            }

                            $reminderWhen = $reminder['when'];

                            if ($reminderWhen[0] === '-') {
                                $reminderDate->sub(new DateInterval(substr($reminderWhen, 1)));
                            }
                            else {
                                $reminderDate->add(new DateInterval($reminderWhen));
                            }

                            $timestamp = $reminderDate->getTimestamp();

                            if (!array_key_exists($timestamp, $dates[$key]['data'])) {
                                $dates[$key]['data'][$timestamp] = [];
                            }

                            $dates[$key]['data'][$timestamp][] = $current;

                            if ($current === 'household' || $current === 'recycling, glass') {
                                $dates[$key]['data'][$timestamp][] = 'food';
                            }
                        }
                    }

                    $string = '';
                }
            }
        }

        $response = new Response();

        $response->headers->add([
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);

        // Not sure how necessary this is for Cloud providers?
        $response->setContent(str_replace("\n", "\r\n", $this->twig->render('app/feed.ical.twig', [
            'icalFormat' => 'Ymd\\THis\\Z',
            'uprn' => $uprn,
            'dates' => $dates,
        ])));

        return $response;
    }
}
