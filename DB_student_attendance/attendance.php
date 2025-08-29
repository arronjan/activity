class Student {
        private $conn;
        private $table_name = "students";

        public $id;
        public $name;
        public $student_id;

        public function __construct($db) {
            $this->conn = $db;
        }

        // Methods for add, list, edit, delete students
        public function addStudent() { /* ... */ }
        public function listStudents() { /* ... */ }
        public function editStudent() { /* ... */ }
        public function deleteStudent() { /* ... */ }
    }