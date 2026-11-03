-- Add difficulty column to company_questions table without data loss
ALTER TABLE company_questions
ADD COLUMN difficulty ENUM('Beginner','Intermediate','Advanced','Pro') NOT NULL DEFAULT 'Beginner';
