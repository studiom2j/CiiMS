<?php

class EmailSettings extends CiiSettingsModel
{
	protected $SMTPHost = NULL;

	protected $SMTPPort = NULL;

	protected $SMTPUser = NULL;

	protected $SMTPPass = NULL;

	protected $notifyName = NULL;

	protected $notifyEmail = NULL;

	protected $useTLS = 0;

	protected $useSSL = 0;

	public function rules()
	{
		return array(
			array('notifyName, notifyEmail', 'required'),
			array('notifyEmail', 'email'),
			array('useTLS, useSSL', 'boolean'),
			array('SMTPPort', 'numerical', 'integerOnly' => true, 'min' => 0),
			array('SMTPPass', 'password'),
			array('notifyName, SMTPPass, SMTPUser', 'length', 'max' => 255)
		);
	}

	public function attributeLabels()
	{
		return array(
			'SMTPHost' => Yii::t('ciims.models.email', 'SMTP Hostname'),
			'SMTPPort' => Yii::t('ciims.models.email', 'SMTP Port Number'),
			'SMTPUser' => Yii::t('ciims.models.email', 'SMTP Username'),
			'SMTPPass' => Yii::t('ciims.models.email', 'SMTP Password'),
			'useTLS' => Yii::t('ciims.models.email', 'Use TLS Connection'),
			'useSSL' => Yii::t('ciims.models.email', 'Use SSL Connection'),
			'notifyName' => Yii::t('ciims.models.email', 'System From Name'),
			'notifyEmail' => Yii::t('ciims.models.email', 'System Email Address')
		);
	}

	/**
	 * Generic method for sending an email. Instead of having to call a bunch of code all over over the place
	 * This method can be called which should be able to handle almost anything.
	 *
	 * By calling this method, the SMTP details will automatically be setup as well the notify email and user
	 *
	 * @param  Users   $user          The User we are sending the email to
	 * @param  string  $subject       The email Subject
	 * @param  string  $viewFile      The view file we want to render. Generally this should be in the form //email/<file>
	 *                                And should correspond to a viewfile in /themes/<theme>/views/email/<file>
	 * @param  array   $content       The content to pass to renderPartial()
	 * @param  boolean $return        Whether the output should be returned. The default is TRUE since this output will be passed to MsgHTML
	 * @param  boolean $processOutput Whether the output should be processed. The default is TRUE since this output will be passed to MsgHTML
	 * @return boolean                Whether or not the email sent sucessfully
	 */
	public function send($user, $subject = "", $viewFile, $content = array(), $return = true, $processOutput = true, $debug=false)
	{
		$mail = new PHPMailer($debug);
		$mail->IsSMTP();
		$mail->SMTPAuth = false;

		$smtpHost    = Cii::getConfig('SMTPHost',    NULL);
		$smtpPort    = Cii::getConfig('SMTPPort',    NULL);
		$smtpUser    = Cii::getConfig('SMTPUser',    NULL);
		$smtpPass    = Cii::getConfig('SMTPPass',    NULL);
		$useTLS      = Cii::getConfig('useTLS',      0);
		$useSSL      = Cii::getConfig('useSSL',      0);

		$notifyUser  = new stdClass;
		if (isset($content['origin_from']))
		{
			$notifyUser->email    = $content['origin_from']['email'];
			$notifyUser->username = $content['origin_from']['name'];
		}
		else
		{
			$notifyUser->email    = Cii::getConfig('notifyEmail', NULL);
			$notifyUser->username = Cii::getConfig('notifyName',  NULL);
		}

		if ($smtpHost !== NULL && $smtpHost !== "")
			$mail->Host       = $smtpHost;

		if ($smtpPort !== NULL && $smtpPort !== "")
			$mail->Port       = $smtpPort;

		if ($smtpUser !== NULL && $smtpUser !== "")
		{
			$mail->Username   = $smtpUser;
			$mail->SMTPAuth = true;
		}

		if ($useTLS == 1)
			$mail->SMTPSecure = 'tls';

		if ($useSSL == 1)
			$mail->SMTPSecure = 'ssl';

		if (!empty($smtpPass))
		{
			$mail->Password   = Cii::decrypt($smtpPass);
			$mail->SMTPAuth = true;
		}

		if ($notifyUser->email == NULL && $notifyUser->username == NULL)
			$notifyUser = Users::model()->findByPk(1);

		$mail->SetFrom($notifyUser->email, $notifyUser->username);
		$mail->Subject = $subject;
		$mail->MsgHTML($this->renderFile(Yii::getPathOfAlias($viewFile).'.php', $content, $return, $processOutput));
		$mail->AddAddress($user->email, $user->username);

		try
		{
			return $mail->Send();
		}
		catch (phpmailerException $e)
		{
			return $debug ? $e->errorMessage() : false;
		}
		catch (Exception $e)
		{
			return $debug ? $e : false;
		}

		return false;
	}

	/**
	 * Renders a view file.
	 *
	 * @param string $viewFile view file path
	 * @param array $data data to be extracted and made available to the view
	 * @param boolean $return whether the rendering result should be returned instead of being echoed
	 * @return string the rendering result. Null if the rendering result is not required.
	 * @throws CException if the view file does not exist
	 */
	private function renderFile($viewFile,$data=null,$return=false)
	{
		if(($renderer=Yii::app()->getViewRenderer())!==null && $renderer->fileExtension==='.'.CFileHelper::getExtension($viewFile))
			$content=$renderer->renderFile($this,$viewFile,$data,$return);
		else
			$content=$this->renderInternal($viewFile,$data,$return);
		
		return $content;
	}

	/**
	 * Renders a view file.
	 * This method includes the view file as a PHP script
	 * and captures the display result if required.
	 * @param string $_viewFile_ view file
	 * @param array $_data_ data to be extracted and made available to the view file
	 * @param boolean $_return_ whether the rendering result should be returned as a string
	 * @return string the rendering result. Null if the rendering result is not required.
	 */
	private function renderInternal($_viewFile_,$_data_=null,$_return_=false)
	{
	    // we use special variable names here to avoid conflict when extracting data
	    if(is_array($_data_))
	        extract($_data_,EXTR_PREFIX_SAME,'data');
	    else
	        $data=$_data_;
	    if($_return_)
	    {
	        ob_start();
	        ob_implicit_flush(false);
	        require($_viewFile_);
	        return ob_get_clean();
	    }
	    else
	        require($_viewFile_);
	}
}
