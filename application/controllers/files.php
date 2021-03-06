<?php 
class Files extends CI_Controller {

	function __construct() {
		parent::__construct();	
		
		$this->load->model(array('Files_Model'));
	}
	
	function testing($entityId) {
		$this->_listing('testing', $entityId);
	}
	
	function _listing($entityName, $entityId, $fileId = null) {
		// TODO: implementar seguridad!!
		
		$result 			= array('files' => array());
		$result['files'] 	= $this->Files_Model->getFilesByEntity($entityName, $entityId, $fileId);
		
		return $this->load->view('ajax', array(
			'result' 	=> $result
		));		
	}
	
	function save() {
		// TODO: implementar seguridat!!
		
		$entityName = $this->input->post('entityName');
		$entityId	= $this->input->post('entityId');
		
		$aProperties = $this->Files_Model->getPropertyByEntityName($entityName);
		
		$config	= array(
			'upload_path' 		=> '.'.$aProperties['folder'].'/original',
			'allowed_types' 	=> 'gif|jpg|png',
			'max_size'			=> 1024 * 8,
			'encrypt_name'		=> false,
			'is_image'			=> true
		);

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload()) {
			return loadViewAjax(false, $this->upload->display_errors('', ''));
		}
		
		$data = $this->upload->data();

		$aSizes = array(
			'thumb'	=> array( 'width' => 150, 	'height' => 100),
			'large'	=> array( 'width' => 1024, 	'height' => 660),
		);
		
		$this->load->library('image_lib');
		
		// creo el thumb y el large
		foreach ($aSizes as $key => $size) {
			$config = array(
				'source_image' 		=> $data['full_path'],
				'new_image' 		=> '.'.$aProperties['folder'] . $key,
				'maintain_ratio' 	=> true,
				'width' 			=> $size['width'],
				'height' 			=> $size['height']
			);
			$this->image_lib->initialize($config);
			$this->image_lib->resize();
		}

			
		$fileId = $this->Files_Model->insert($data['file_name'], '' /*$_POST['title']*/); // TODO:
		if(!$fileId) {
			unlink($data['full_path']);
			return loadViewAjax(false, 'Something went wrong when saving the file, please try again');
		}
					
		$this->Files_Model->saveFileRelation($entityName, $fileId, $entityId);
		@unlink($_FILES[$file_element_name]);
		
		$this->_listing($entityName, $entityId, $fileId);
	}

	function remove($entityName, $entityId, $fileId) {
		// TODO: implementar la seguridad!
		$this->Files_Model->deleteByFileId($entityName, $entityId, $fileId);
		
		return loadViewAjax(true, array());
	}
}