<?php namespace Orchestra\Tests\Routing;

\Bundle::start('orchestra');

class BundleTest extends \Orchestra\Testable\TestCase {

	/**
	 * User instance
	 *
	 * @var Orchestra\Model\User
	 */
	private $user = null;

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		parent::setUp();

		$base_path = \Bundle::path('orchestra').'tests'.DS.'fixtures'.DS;
		set_path('app', $base_path.'application'.DS);
		set_path('orchestra.extension', $base_path.'bundles'.DS);

		$_SERVER['orchestra.publishing'] = array();

		$this->user = \Orchestra\Model\User::find(1);
	}

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		unset($this->user);
		$this->be(null);

		unset($_SERVER['orchestra.publishing']);

		$base_path = path('base');
		set_path('app', $base_path.'application'.DS);
		set_path('orchestra.extension', $base_path.'bundles'.DS);
		
		parent::tearDown();
	}

	/**
	 * Test Request GET (orchestra)/bundles/update/:name when not logged-in
	 *
	 * @test
	 * @group routing
	 */
	public function testGetUpdateWhenNotAuth()
	{
		$this->be(null);

		$response = $this->call('orchestra::bundles@update', array('aws'));

		$this->assertInstanceOf('\Laravel\Redirect', $response);
		$this->assertEquals(302, $response->foundation->getStatusCode());
		$this->assertEquals(handles('orchestra::login'), 
			$response->foundation->headers->get('location'));
	}

	/**
	 * Test Request GET (orchestra)/bundles/update/:name when logged-in
	 *
	 * @test
	 * @group routing
	 */
	public function testGetUpdateWhenAuth()
	{
		$this->be($this->user);

		\Event::listen('orchestra.publishing: extension', function ($name)
		{
			$_SERVER['orchestra.publishing'][] = $name;
		});

		$response = $this->call('orchestra::bundles@update', array('aws'));

		$this->assertInstanceOf('\Laravel\Redirect', $response);
		$this->assertEquals(302, $response->foundation->getStatusCode());
		$this->assertEquals(handles('orchestra'), 
			$response->foundation->headers->get('location'));

		$this->assertEquals(array('aws'), $_SERVER['orchestra.publishing']);
	}

	/**
	 * Test Request GET (orchestra)/bundles/update/:name when provided bundle
	 * is invalid
	 *
	 * @test
	 * @group routing
	 */
	public function testGetUpdateInvalidBundle()
	{
		$this->be($this->user);

		$response = $this->call('orchestra::bundles@update', array('invalid-bundle-does-not-exist'));

		$this->assertInstanceOf('\Laravel\Response', $response);
		$this->assertEquals(404, $response->foundation->getStatusCode());
	}

	/**
	 * Test update bundle failed with publisher error.
	 *
	 * @test
	 * @group routing
	 */
	public function testGetUpdateFailedPublisherError()
	{
		$this->be($this->user);

		$events = \Event::$events;
		\Event::listen('orchestra.publishing: extension', function ($name)
		{
			throw new \Orchestra\Extension\FilePermissionException();
		});

		$response = $this->call('orchestra::bundles@update', array('aws'));

		$this->assertInstanceOf('\Laravel\Redirect', $response);
		$this->assertEquals(302, $response->foundation->getStatusCode());
		$this->assertEquals(handles('orchestra::publisher'), 
			$response->foundation->headers->get('location'));

		\Event::$events = $events;
	}
}