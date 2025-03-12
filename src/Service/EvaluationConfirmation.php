<?php

namespace Drupal\event_evaluation\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\easy_email\Service\EmailHandlerInterface;
use Drupal\node\Entity\Node;

class EvaluationConfirmation
{

    protected $mailer;
    protected $logger;

    public function __construct(EmailHandlerInterface $mailer, LoggerChannelFactoryInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger->get(get_class());
    }

    public function sendConfirmationEmail(Node $node)
    {
        try {
            $email = $this->mailer->createEmail([
                'type' => 'et_evaluation_confirmation',
                'label' =>  $node->getTitle() . ' evaluation confirmation'
            ]);
            if ($email) {
                $email->set('field_event', $node);
                $email->setRecipientIds([$node->getOwnerId()]);
                $this->mailer->sendEmail($email, [], true, true);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }
}
